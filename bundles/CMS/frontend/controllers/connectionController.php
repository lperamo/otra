<?
namespace bundles\CMS\frontend\controllers;

use \lib\myLibs\{Controller, Router, bdd\Sql, Lionel_Exception};
use \bundles\CMS\models\User;
//    ,Session;

/**
 * LPCMS Connection management
 *
 * @author Lionel Péramo
 */
class connectionController extends Controller
{
  public function ajaxLoginAction()
  {
    false === isset($_POST['email'], $_POST['pwd']) || 2 < count($_POST) && die('Hack.');

    $email = $_POST['email'];

    if (true === empty($email))
      throw new Lionel_Exception('Missing email !');

    $pwd = $_POST['pwd'];

    if (true === empty($pwd))
      throw new Lionel_Exception('Missing password !');

    Sql::getDB();

    $infosUser = User::auth($email, crypt($pwd, FWK_HASH));

    if (true === empty($infosUser))
    {
      echo '{"0": "Bad credentials."}';

      return;
    }

    $_SESSION['sid'] = [
      'uid' => $infosUser['id_user'],
      'role' => $infosUser['fk_id_role']
    ];

    echo '{"status": 1}';
  }

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
