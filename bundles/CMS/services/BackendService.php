<?
/**
 * LPCMS - Backend service
 *
 * @author Lionel Péramo */

namespace bundles\CMS\services;

use lib\myLibs\Router;

// TODO use the framework secure sesion mechanism instead of $_SESSION ?

class BackendService
{
  /**
   * @param string $route
   *
   * @return bool False, if we must exit the application
   */
  public static function checkConnection(string $route)
  {
    // TODO make two conditions depending on the side we are ...frontend or backend
    if (in_array($route, ['showArticle', 'logout', 'ajaxShowArticle', 'ajaxConnection', 'ajaxMailingList']) === false
      && false === isset($_SESSION['sid']))
    {
      // We must not loop on this route !
      if ($route === 'backend')
        return false;

      $_SESSION['previousRoute'] = $route;
      Router::get('backend');
      return false;
    }

    return true;
  }
}
?>
