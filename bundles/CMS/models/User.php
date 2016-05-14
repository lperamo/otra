<?

namespace bundles\CMS\models;

use lib\myLibs\bdd\Sql;

/**
 * LPCMS User model
 *
 * @author Lionel Péramo
 */
class User
{
  public static function checkPseudo($pseudo)
  {
    $dbUsers = Sql::$instance->query(
      'SELECT pseudo FROM lpcms_user
       WHERE pseudo = \'' . $pseudo . '\' LIMIT 1'
    );
    $users = Sql::$instance->values($dbUsers);
    Sql::$instance->freeResult($dbUsers);

    return $users;
  }

  /**
   * Checks if we already have that pseudo in the database and it's different from the pseudo passed in parameter.
   * Returns true if there is a problem.
   * @param $pseudo    Wanted pseudo
   * @param $oldPseudo The old pseudo
   * @return bool
   */
  public static function checkPseudoEdit($pseudo, $oldPseudo)
  {
    $users = self::checkPseudo($pseudo);

    return is_array($users) && $oldPseudo != $pseudo;
  }

  public static function checkMail($mail)
  {
    $dbUsers = Sql::$instance->query(
      'SELECT mail FROM lpcms_user
       WHERE mail = \'' . $mail . '\' LIMIT 1'
    );

    $users = Sql::$instance->single($dbUsers);
    Sql::$instance->freeResult($dbUsers);

    return $users;
  }

  /**
   * Checks if we already have that mail in the database and it's different from the mail passed in parameter.
   * Returns true if there is a problem.
   * @param $mail    string Wanted mail
   * @param $oldMail string The old mail
   * @return bool
   */
  public static function checkMailEdit($mail, $oldMail)
  {
    $users = self::checkMail($mail);

    return is_array($users) && $oldMail != $mail;
  }

  /**
   * Parameters sanitized in the function.
   *
   * @param string $mail
   * @param string $pwd
   * @param string $pseudo
   * @param int $role
   */
  public static function addUser($mail, $pwd, $pseudo, $role)
  {
    Sql::$instance->query(
      'INSERT INTO lpcms_user (`mail`, `pwd`, `pseudo`, `fk_id_role`)
       VALUES (\'' . Sql::$instance->quote($mail) . '\', \'' . Sql::$instance->quote($pwd) . '\', \'' . Sql::$instance->quote($pseudo) . '\', ' . intval($role) . ');'
    );
  }

  /**
   * @param $limit
   */
  public static function getFirstUsers($limit)
  {
    return Sql::$instance->values(Sql::$instance->query(
       'SELECT u.id_user, u.mail, u.pwd, u.pseudo, r.id as fk_id_role, r.nom FROM lpcms_user u
       INNER JOIN lpcms_role r ON u.fk_id_role = r.id
       ORDER BY id_user
       LIMIT ' . intval($limit)
     ));
  }

  /**
   * Parameters sanitized in the function : pwd, role, intval.
   *
   * @param int    $id_user
   * @param string $mail
   * @param string $pwd
   * @param string $pseudo
   * @param int    $role
   */
  public static function updateUser($id_user, $mail, $pwd, $pseudo, $role)
  {
    return Sql::$instance->query(
      'UPDATE lpcms_user SET
      mail = \'' . $mail . '\',
      pwd = \'' . Sql::$instance->quote($pwd) . '\',
      pseudo = \'' . $pseudo . '\',
      fk_id_role = ' . intval($role) . ' WHERE id_user = ' . intval($id_user)
    );
  }

  /**
   * Adds a field to the search
   *
   * @param  string $field     Field to add
   * @param  mixed  $value     Value of the field
   * @param  bool   $search    Search(true) or pagination(false)
   * @param  bool   &$sqlStart Is the request starting ?
   * @param  string &$req      Request being made
   */
  private static function searchPart($field, $value, $search, &$sqlStart, &$req)
  {
    if('' != $value)
    {
      if($search && $sqlStart)
        $req .= ' WHERE ';

      if(!$sqlStart)
        $req .= ' AND ';

      $req .= $field . ' LIKE \'%' . Sql::$instance->quote($value) . '%\'';
      $sqlStart = false;
    }
  }

  /**
   * Parameters sanitized in the function. 2 requests !
   *
   * @param $userParams array [$type, $prev, $last, $limit, $mail, $pseudo, $role]
   *
   * @return $users
   */
  public static function search($userParams)
  {
    extract($userParams);
    $limit = intval($limit);
    $req = ' FROM lpcms_user u
      INNER JOIN lpcms_role r ON u.fk_id_role = r.id';
    $search = 'search' == $type;
    $cond = 'next' == $type || $search;
    $sqlStart = true;

    if(!$search)
      $req .= ' WHERE id_user ' . ('next' == $type
        ? '> ' . intval($last)
        : '< ' . intval($prev)
        );

    // Search by mail ?
    if('' != $mail)
    {
      $req .= ($search && $sqlStart ? ' WHERE' : ' AND') . ' u.mail LIKE \'%' . Sql::$instance->quote($mail) . '%\'';
      $sqlStart = false;
    }

    self::searchPart('u.pseudo', $pseudo, $search, $sqlStart, $req);
    self::searchPart('r.nom', $role, $search, $sqlStart, $req);

    $req .= ' ORDER BY u.id_user ';

    $result = Sql::$instance->query('SELECT u.id_user, u.mail, u.pwd, u.pseudo, r.id, r.nom' . $req . ($cond ? 'LIMIT ' : 'DESC LIMIT ') . $limit);

    return $search
      ? [
          $result,
          Sql::$instance->single(Sql::$instance->query('SELECT COUNT(u.id_user) ' . $req . ($cond ? '' : 'DESC')))
        ]
      : $result;
  }

  /**
  * @return int Users count
  */
  public static function count()
  {
    return Sql::$instance->single(Sql::$instance->query('SELECT COUNT(id_user) FROM lpcms_user'));
  }

  /**
  * @param array Roles
  */
  public static function getRoles()
  {
    return Sql::$instance->values(Sql::$instance->query('SELECT id, nom FROM lpcms_role ORDER BY nom ASC'));
  }

  /**
  * Parameter intvaled in the function.
  *
  * @param int $id_user
  *
  * @return bool Success ?
  */
  public static function delete($id_user)
  {
    return Sql::$instance->query('DELETE FROM lpcms_user WHERE `id_user` = ' . intval($id_user));
  }

  /**
   * Checks if the user can be logged. Parameters sanitized in the function.
   *
   * @param  string $email
   * @param  string $pwd
   *
   * @return array [id_user, fk_id_role]
   */
  public static function auth($email, $pwd)
  {

    return Sql::$instance->fetchAssoc(
      Sql::$instance->query('SELECT u.`id_user`, u.`fk_id_role`
       FROM lpcms_user u
       WHERE u.`mail` = \'' . Sql::$instance->quote($email) . '\'
        AND u.`pwd` = \'' . Sql::$instance->quote($pwd) . '\'
        LIMIT 1')
    );
  }
}
?>
