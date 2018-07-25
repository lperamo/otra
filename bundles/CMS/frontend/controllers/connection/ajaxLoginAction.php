<?
namespace bundles\CMS\frontend\controllers\connection;

use bundles\CMS\models\User;
use lib\myLibs\{ bdd\Sql, Controller, Lionel_Exception, Router };

/**
 * LPCMS - Frontend - Connection - AjaxLogin
 *
 * @author Lionel Péramo
 */
class ajaxLoginAction extends Controller
{
  public function ajaxLoginAction()
  {
    if (false === isset($_POST['email'], $_POST['pwd']) || 2 < count($_POST))
    {
      echo 'Hack.';
      return;
    }

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

    $_SESSION['sid'] =
    [
      'uid' => $infosUser['id_user'],
      'role' => $infosUser['fk_id_role']
    ];

    echo '{"status": 1, "url": "' . Router::getRouteUrl(isset($_SESSION['previousRoute']) === true
      ? $_SESSION['previousRoute']
      : 'backendModules') .
      '"}';
  }
}
?>
