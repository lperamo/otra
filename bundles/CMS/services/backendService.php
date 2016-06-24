<?
/**
 * LPCMS - Backend service
 *
 * @author Lionel Péramo */

namespace bundles\CMS\services;

use config\Router;
//  lib\myLibs\Session,

class backendService
{
  /**
   * @param string $action
   */
  public static function checkConnection(string $action)
  {
    if ('index' !== $action && false === isset($_SESSION['sid']))
    {
      Router::get('backend');
      die;
    }
  }
}
?>
