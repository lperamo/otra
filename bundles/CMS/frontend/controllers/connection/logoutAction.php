<?
namespace bundles\CMS\frontend\controllers\connection;

use \lib\myLibs\{Controller, Router};

/**
 * LPCMS - Frontend - Connection - Logout
 *
 * @author Lionel Péramo
 */
class logoutAction extends Controller
{
  public function logoutAction()
  {
    unset($_SESSION['sid']);
    $backendRouteUrl = Router::getRouteUrl('backend');

    // PHP redirects the user to the frontend page or the backend page according to what he's actually viewing.
    header('Location: ' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] .
      (false === isset($_SERVER['HTTP_REFERER']) || false === strpos($_SERVER['HTTP_REFERER'], $backendRouteUrl) ? '/' : $backendRouteUrl)
    );
  }
}
?>
