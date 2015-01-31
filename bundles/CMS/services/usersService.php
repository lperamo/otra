<?
/**
 * Users service
 *
 * @author Lionel Péramo */

namespace bundles\CMS\backend\services;

use lib\myLibs\core\bdd\Sql,
    lib\myLibs\core\Session;

class usersService
{
  /**
   * @return array [$users, $count]
  */
  public static function getUsersTab()
  {
    $db = Session::get('dbConn');
    $db->selectDb();

    // Retrieving the users
    $users = $db->values($db->query(
      'SELECT u.id_user, u.mail, u.pwd, u.pseudo, r.id_role, r.nom FROM lpcms_user u
      INNER JOIN lpcms_role r ON u.role_id = r.id_role
      ORDER BY id_user
      LIMIT 3'
    ));

    // Fixes the bug where there is only one user
    if(isset($users['id_user']))
      $users = array($users);

    $count = $db->single($db->query('SELECT COUNT(id_user) FROM lpcms_user'));
    return array($users, $count);
  }
}
?>
