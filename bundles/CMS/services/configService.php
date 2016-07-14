<?
/**
 * Configuration service
 *
 * @author Lionel Péramo */

namespace bundles\CMS\services;

use lib\myLibs\bdd\Sql,
    bundles\CMS\models\Config;

class configService
{
  /**
   * @return array $configuration
  */
  public static function getConfigTab()
  {
    Sql::getDB();

    return [
      'config' => Config::getAllConfigurablesByUserId($_SESSION['sid']['uid'])
    ];
  }
}
?>
