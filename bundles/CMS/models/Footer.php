<?

namespace bundles\CMS\models;

use lib\myLibs\core\bdd\Sql;

/**
 * LPCMS Footer model
 *
 * @author Lionel Péramo
 */
class Footer
{
  /**
   * @return array $footers
   */
  public static function getAll()
  {
    return Sql::$instance->values(Sql::$instance->query('SELECT * FROM lpcms_footer'));
  }
}
?>
