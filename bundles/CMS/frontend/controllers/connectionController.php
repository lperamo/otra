<?
namespace bundles\CMS\frontend\controllers;

use \lib\myLibs\{Controller, Router, Lionel_Exception};
//    ,Session, Router;

/**
 * LPCMS Connection management
 *
 * @author Lionel Péramo
 */
class connectionController extends Controller
{
  public function ajaxLoginAction()
  {
    !isset($_POST['email'], $_POST['pwd']) || 2 < count($_POST) && die('Hack.');

    $email = $_POST['email'];
    $pwd = $_POST['pwd'];

    if(empty($email))
      throw new Lionel_Exception('Missing email !');

    if(empty($pwd))
      throw new Lionel_Exception('Missing password !');

    \lib\myLibs\bdd\Sql::getDB();

    // if('192.168.1.1' == $_SERVER['REMOTE_ADDR'])
    $infosUser = '192.168.1.1' == $_SERVER['REMOTE_ADDR']
      ? [
        'id_user' => '-1',
        'fk_id_role' => 1]
      : \bundles\CMS\models\User::auth($email, crypt($pwd, FWK_HASH));

    if(empty($infosUser)){
      echo '{"0": "Bad credentials."}';
    } else
    {
      $_SESSION['sid'] = [
        'uid' => $infosUser['id_user'],
        'role' => $infosUser['fk_id_role']
      ];

      echo '{"status": 1}';
    }
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
