<?
namespace bundles\CMS\frontend\controllers\connection;

use \lib\myLibs\{Controller, bdd\Sql, Lionel_Exception};
use \bundles\CMS\models\User;

/**
 * LPCMS - Frontend - Connection - AjaxLogin
 *
 * @author Lionel Péramo
 */
class ajaxLoginAction extends Controller
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
}
?>
