<?

namespace bundles\CMS\models;

use lib\myLibs\core\bdd\Sql;

/**
 * LPCMS Config model
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
       WHERE c.`key` = \'' . Sql::$instance->quote($key) . '\' AND cfg.key = \'' . Sql::$instance->quote($type) . '\' LIMIT 1'
    );
    $value = Sql::$instance->single($dbConfig);
    Sql::$instance->freeResult($dbConfig);

    return $value;
  }

  /**
   * @param  int $id_user
   * @return [type]          [description]
   */
  public static function getAllConfigurablesByUserId($id_user)
  {
    $dbConfig = Sql::$instance->query(
      'SELECT c.id, c.type, c.title, c.value,  cfg.name
       FROM lpcms_config c
       INNER JOIN lpcms_types_cfg cfg ON c.type_cfg_id = cfg.id
       WHERE c.user_id = ' . $id_user . ' OR c.user_id IS NULL'
    );
    $values = Sql::$instance->values($dbConfig);
    Sql::$instance->freeResult($dbConfig);

    return $values;
  }
}
?>
