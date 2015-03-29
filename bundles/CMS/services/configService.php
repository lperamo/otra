<?
/**
 * Configuration service
 *
 * @author Lionel Péramo */

namespace bundles\CMS\services;

use lib\myLibs\core\bdd\Sql,
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
      'config' => \bundles\CMS\models\Config::getAllConfigurablesByUserId($_SESSION['sid']['uid'])
    ];
  }
}
?>
