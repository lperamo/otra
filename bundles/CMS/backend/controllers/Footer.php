<?

namespace bundles\CMS\models;



/**
 * LPCMS Footer model
 *
 * @author Lionel Péramo
 */
class Footer
{
  /**
   * @return $headers
   */
  public static function getAll()
  {
    return Sql::$instance->values(Sql::$instance->query('SELECT * FROM lpcms_footer'));
  }
}
?>
