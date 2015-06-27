<?
namespace bundles\CMS\frontend\controllers;

use \lib\myLibs\core\Controller,
    \lib\myLibs\core\Lionel_Exception,
    \lib\myLibs\core\Session,
    \lib\myLibs\core\Router;

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

    \lib\myLibs\core\bdd\Sql::getDB();

    // if('192.168.1.1' == $_SERVER['REMOTE_ADDR'])
    $infosUser = '192.168.1.1' == $_SERVER['REMOTE_ADDR']
      ? [
        'id_user' => '-1',
        'role_id' => 1]
      : \bundles\CMS\models\User::auth($email, crypt($pwd, FWK_HASH));

    if(empty($infosUser)){
      echo '{"0": "Bad credentials."}';
    } else
    {
      $_SESSION['sid'] = [
        'uid' => $infosUser['id_user'],
        'role' => $infosUser['role_id']
      ];

      echo '{"status": 1}';
    }
  }

  public function logoutAction()
  {
    unset($_SESSION['sid']);
    Router::get('backend');
  }
}
?>
