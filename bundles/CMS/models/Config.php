<?

namespace bundles\CMS\models;

use lib\myLibs\core\bdd\Sql;

/**
 * LPCMS User model
 *
 * @author Lionel Péramo
 */
class Config
{
  /**
   * Already mysql_real_escaped !
   *
   * @param string $type
   * @param string $key
   *
   * @return mixed $value
   */
  public static function getByTypeAndKey($type, $key)
  {
    $dbConfig = Sql::$instance->query(
      'SELECT value
       FROM lpcms_config c
       INNER JOIN lpcms_types_cfg cfg ON c.type_cfg_id = cfg.id
       WHERE cfg.nom = \'' . mysql_real_escape_string($type) . '\' AND `key` = \'' . mysql_real_escape_string($key) . '\' LIMIT 1'
    );
    $value = Sql::$instance->single($dbConfig);
    Sql::$instance->freeResult($dbConfig);

    return $value;
  }
}
?>
