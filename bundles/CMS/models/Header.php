<?

namespace bundles\CMS\models;

use lib\myLibs\bdd\Sql;

/**
 * LPCMS Header model
 *
 * @author Lionel Péramo
 */
class Header
{
  /**
   * @return $headers
   */
  public static function getAll()
  {
    return Sql::$instance->values(Sql::$instance->query('SELECT * FROM lpcms_header'));
  }
}
?>
