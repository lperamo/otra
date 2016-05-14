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
  /**
   * @return $headers
   */
  public static function getAll()
  {
    return Sql::$instance->values(Sql::$instance->query('SELECT * FROM lpcms_module'));
  }
}
?>
