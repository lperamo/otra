<?
namespace bundles\CMS\frontend\controllers\connection;

use bundles\CMS\models\User;
use lib\myLibs\{ bdd\Sql, Controller, LionelException, Router };

/**
 * LPCMS - Frontend - Connection - AjaxLogin
 *
 * @author Lionel Péramo
 */
class ajaxLoginAction extends Controller
{
  /**
   * @throws LionelException
   */
  public function ajaxLoginAction()
  {
    if (false === isset($_POST['email'], $_POST['pwd']) || 2 < count($_POST))
    {
      echo 'Hack.';
      return;
    }

    $email = $_POST['email'];

    if (true === empty($email))
      throw new LionelException('Missing email !');

    $pwd = $_POST['pwd'];

    if (true === empty($pwd))
      throw new LionelException('Missing password !');

    Sql::getDB();

    $infosUser = User::auth($email, crypt($pwd, FWK_HASH));

    if (true === empty($infosUser))
    {
      echo '{"0": "Bad credentials."}';

      return;
    }

    $_SESSION['sid'] =
    [
      'uid' => (int) $infosUser['id_user'],
      'role' => $infosUser['fk_id_role']
    ];

    echo '{"status": 1, "url": "' . Router::getRouteUrl(isset($_SESSION['previousRoute']) === true
      ? $_SESSION['previousRoute']
      : 'backendModules') .
      '"}';
  }
}
?>
