<?

namespace bundles\CMS\models;

use lib\myLibs\bdd\Sql;

/**
 * LPCMS Module model
 *
 * @author Lionel Péramo
 */
class Module
{
  public static $moduleTypes = [
    0 => 'Connection',
    1 => 'Vertical menu',
    2 => 'Horizontal menu',
    3 => 'Article',
    4 => 'Arbitrary'
  ], $rights = [
    0 => 'Admin',
    1 => 'Saved',
    2 => 'Public'
  ];

  /**
   * @return $headers
   */
  public static function getAll()
  {
    return Sql::$instance->values(Sql::$instance->query('SELECT * FROM lpcms_module'));
  }
}
?>
