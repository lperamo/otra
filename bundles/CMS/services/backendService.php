<?
/**
 * LPCMS - Backend service
 *
 * @author Lionel Péramo */

namespace bundles\CMS\services;

use lib\myLibs\Router;

// TODO use the framework secure sesion mechanism instead of $_SESSION ?

class backendService
{
  /**
   * @param string $route
   *
   * @return bool False, if we must exit the application
   */
  public static function checkConnection(string $route)
  {
    if (in_array($route, ['backend', 'showArticle', 'logout', 'ajaxShowArticle', 'ajaxConnection', 'ajaxMailingList', 'index']) === false
      && false === isset($_SESSION['sid']))
    {
      $_SESSION['previousRoute'] = $route;
      Router::get('backend');
      return false;
    }

    return true;
  }
}
?>
