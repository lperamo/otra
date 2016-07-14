<?
/**
 * LPCMS - Backend service
 *
 * @author Lionel Péramo */

namespace bundles\CMS\services;

use lib\myLibs\Router;
//  lib\myLibs\Session,

class backendService
{
  /**
   * @param string $action
   *
   * @return bool False, if we must exit the application
   */
  public static function checkConnection(string $action)
  {
    if ('index' !== $action && false === isset($_SESSION['sid']))
    {
      Router::get('backend');
      return false;
    }

    return true;
  }
}
?>
