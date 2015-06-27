<?

namespace bundles\CMS\models;

use lib\myLibs\core\bdd\Sql;

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
//    dump(Sql::$instance->values(Sql::$instance->query('SELECT * FROM lpcms_header')));
//    die;
    return Sql::$instance->values(Sql::$instance->query('SELECT * FROM lpcms_header'));
  }
}
?>
