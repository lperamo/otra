<?
/**
 * Users service
 *
 * @author Lionel Péramo */

namespace bundles\CMS\services;

use lib\myLibs\core\bdd\Sql,
    lib\myLibs\core\Session,
    bundles\CMS\models\User;

class usersService
{
  /**
   * @return array [$roles, $users, $count]
  */
  public static function getUsersTab()
  {
    $db = Session::get('dbConn');
    $db->selectDb();

    // Retrieving the users
    $users = User::getFirstUsers($db, 3);

    // Fixes the bug where there is only one user
    if(isset($users['id_user']))
      $users = array($users);

    $count = User::count($db);

    return array(User::getRoles($db), $users, $count);
  }
}
?>
