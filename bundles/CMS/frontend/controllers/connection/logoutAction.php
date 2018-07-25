<?
namespace bundles\CMS\frontend\controllers\connection;

use lib\myLibs\Controller;

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
    header('Refresh:0;url=' . $_SERVER['HTTP_REFERER']);
  }
}
?>
