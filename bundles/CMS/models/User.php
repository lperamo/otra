<?

namespace bundles\CMS\models;

use lib\myLibs\core\bdd\Sql;

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
   *
   * @param $pseudo    Wanted pseudo
   * @param $oldPseudo The old pseudo
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
   *
   * @param $mail    Wanted mail
   * @param $oldMail The old mail
   */
  public static function checkMailEdit($mail, $oldMail)
  {
    $users = self::checkMail($mail);

    return is_array($users) && $oldMail != $mail;
  }

  /**
   * Already mysql_real_escaped !
   *
   * @param string $mail
   * @param string $pwd
   * @param string $pseudo
   * @param int $role
   */
  public static function addUser($mail, $pwd, $pseudo, $role)
  {
    Sql::$instance->query(
      'INSERT INTO lpcms_user (`mail`, `pwd`, `pseudo`, `role_id`)
       VALUES (\'' . mysql_real_escape_string($mail) . '\', \'' . mysql_real_escape_string($pwd) . '\', \'' . mysql_real_escape_string($pseudo) . '\', ' . intval($role) . ');'
    );
  }

  /**
   * @param $limit
   */
  public static function getFirstUsers($limit)
  {
    return Sql::$instance->values(Sql::$instance->query(
       'SELECT u.id_user, u.mail, u.pwd, u.pseudo, r.id, r.nom FROM lpcms_user u
       INNER JOIN lpcms_role r ON u.role_id = r.id
       ORDER BY id_user
       LIMIT ' . $limit
     ));
  }

  /**
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
      pwd = \'' . mysql_real_escape_string($pwd) . '\',
      pseudo = \'' . $pseudo . '\',
      role_id = ' . intval($role) . ' WHERE id_user = ' . intval($id_user)
    );
  }

  /**
   * Already mysql_real_escaped !
   *
   * @param $userParams [$type, $prev, $last, $limit, $mail, $pseudo, $role]
   *
   * @return $users
   */
  public static function search($userParams)
  {
    extract($userParams);
    $limit = intval($limit);
    $req = 'SELECT u.id_user, u.mail, u.pwd, u.pseudo, r.id, r.nom FROM lpcms_user u
      INNER JOIN lpcms_role r ON u.role_id = r.id
      WHERE id_user ';

    if('search' == $type)
      $req .= '> ' . (intval($last) - $limit);
    else
      $req .= ('next' == $type)
        ? '> ' . intval($last)
        : '< ' . intval($prev);

    if('' != $mail)
      $req .= ' AND u.mail LIKE \'%' . mysql_real_escape_string($mail) . '%\'';

    if('' != $pseudo)
      $req .= ' AND u.pseudo LIKE \'%' . mysql_real_escape_string($pseudo) . '%\'';

    if('' != $role)
      $req .= ' AND r.nom LIKE \'%' . mysql_real_escape_string($role) . '%\'';

    return Sql::$instance->query(
      $req . ' ORDER BY u.id_user ' .
      (('next' == $type) ? 'LIMIT ' : 'DESC LIMIT ') . $limit
    );
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
  * @param bool Success ?
  */
  public static function delete($id_user)
  {
    return Sql::$instance->query('DELETE FROM lpcms_user WHERE `id_user` = ' . intval($id_user));
  }
}
?>
