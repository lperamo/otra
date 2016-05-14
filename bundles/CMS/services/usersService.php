<?
/**
 * Users service
 *
 * @author Lionel Péramo
 */

namespace bundles\CMS\services;

use lib\myLibs\bdd\Sql,
    bundles\CMS\models\User;

class usersService
{
  /**
   * @return array [$roles, $users, $count]
  */
  public static function getUsersTab()
  {
    Sql::getDB();
    $limit = \bundles\CMS\models\Config::getByTypeAndKey('user', 'show_limit');

    // If no limit is stored into the database, we fix it at 10
    if (false === $limit)
      $limit = 10;

    $users = User::getFirstUsers($limit);

    // Fixes the bug where there is only one user
    if(isset($users['id_user']))
      $users = [$users];

    $count = User::count();

    return [
      'count' => !empty($count) ? $count : '',
      'limit' => $limit,
      'roles' => User::getRoles(),
      'users' => $users
    ];
  }
}
?>
