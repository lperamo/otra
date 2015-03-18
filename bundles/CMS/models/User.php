<?php

namespace bundles\CMS\models;

/**
 * LPCMS Mailing List model
 *
 * @author Lionel Péramo
 */
class User
{
  public static function checkPseudo($db, $pseudo)
  {
    $dbUsers = $db->query(
      'SELECT pseudo FROM lpcms_user
       WHERE pseudo = \'' . $pseudo . '\' LIMIT 1'
    );
    $users = $db->values($dbUsers);
    $db->freeResult($dbUsers);

    return $users;
  }

  public static function checkMail($db, $mail)
  {
    $dbUsers = $db->query(
      'SELECT mail FROM lpcms_user
       WHERE mail = \'' . $mail . '\' LIMIT 1'
    );

    $users = $db->single($dbUsers);
    $db->freeResult($dbUsers);

    return $users;
  }

  /**
   * Checks if we already have that mail in the database.
   *
   * @param $db      Database connection
   * @param $mail    Wanted mail
   */
  public static function checkMailAdd($db, $mail)
  {
    return self::checkMail($db, $mail);
  }

  /**
   * Checks if we already have that mail in the database and it's different from the mail passed in parameter.
   *
   * @param $db      Database connection
   * @param $mail    Wanted mail
   * @param $oldMail The old mail
   */
  public static function checkMailEdit($db, $mail, $oldMail)
  {
    $users = self::checkMail($db, $mail);

    return is_array($users) && $oldMail != $users[0]['mail'];
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
    $db->query(
      'INSERT INTO lpcms_user (`mail`, `pwd`, `pseudo`, `role_id`)
       VALUES (\'' . mysql_real_escape_string($mail) . '\', \'' . mysql_real_escape_string($pwd) . '\', \'' . mysql_real_escape_string($pseudo) . '\', ' . intval($role) . ');'
    );
  }

  /**
   * @param $limit
   */
  public static function getFirstUsers($db, $limit)
  {
    return $db->values($db->query(
       'SELECT u.id_user, u.mail, u.pwd, u.pseudo, r.id_role, r.nom FROM lpcms_user u
       INNER JOIN lpcms_role r ON u.role_id = r.id_role
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
    return $db->query(
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
   * @param $db Database connection
   * @param $userParams [$type, $prev, $last, $limit, $mail, $pseudo, $role]
   *
   * @return $users
   */
  public static function search($db, $userParams)
  {
    extract($userParams);
    $limit = intval($limit);
    $req = 'SELECT u.id_user, u.mail, u.pwd, u.pseudo, r.id_role, r.nom FROM lpcms_user u
      INNER JOIN lpcms_role r ON u.role_id = r.id_role
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

    return $db->query(
      $req . ' ORDER BY u.id_user ' .
      (('next' == $type) ? 'LIMIT ' : 'DESC LIMIT ') . $limit
    );
  }

  /**
  * @param $db Database connection
  */
  public static function count($db)
  {
    return $db->single($db->query('SELECT COUNT(id_user) FROM lpcms_user'));
  }

  /**
  * @param $db Database connection
  */
  public static function getRoles($db)
  {
    return $db->values($db->query('SELECT id_role, nom FROM lpcms_role ORDER BY nom ASC'));
  }
}
?>
