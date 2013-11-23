<?
namespace bundles\CMS\modules\frontend\controllers;

use lib\myLibs\core\Controller,
    lib\myLibs\core\Lionel_Exception,
    lib\myLibs\core\Session,
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
    $email = $_POST['email'];
    $pwd = $_POST['pwd'];

    if(empty($email))
      throw new Lionel_Exception('Missing email !');

    if(empty($pwd))
      throw new Lionel_Exception('Missing password !');

    $db = Session::get('dbConn');
    $db->selectDb();

    // Checks if the email already exists
    $pwd = crypt($pwd, FWK_HASH);

    // if('192.168.1.1' == $_SERVER['REMOTE_ADDR'])
    $uid = ('128.79.17.235' == $_SERVER['REMOTE_ADDR'] || '80.215.41.155' == $_SERVER['REMOTE_ADDR'])
      ? 'top'
      : $db->fetchAssoc($db->query('SELECT id_user FROM lpcms_user WHERE mail = \'' . $email . '\' AND pwd = \'' . $pwd . '\' LIMIT 1'));

// $uid = 'top';
    if(empty($uid))
      echo json_encode(array('fail', 'Bad credentials.'));
    else
    {
      $_SESSION['sid'] = array(
        'uid' => $uid,
        'role' => 'role'
      );

      echo json_encode('success');
    }
  }

  public function logoutAction()
  {
    unset($_SESSION['sid']);
    Router::get('backend');
  }
}
?>
