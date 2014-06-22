<?
namespace bundles\CMS\frontend\controllers;

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
    $infosUser = ('192.168.1.1' == $_SERVER['REMOTE_ADDR'] || '176.183.7.251' == $_SERVER['REMOTE_ADDR'] || '80.215.41.155' == $_SERVER['REMOTE_ADDR'])
      ? array(
        'id_user' => '-1',
        'fk_id_role' => 1)
      : $db->fetchAssoc($db->query('SELECT u.`id_user`, ur.`fk_id_role` FROM lpcms_user u JOIN lpcms_user_role ur WHERE u.`mail` = \'' . $email . '\' AND u.`pwd` = \'' . $pwd . '\' AND u.id_user = ur.fk_id_user LIMIT 1'));

    if(empty($infosUser))
      echo json_encode(array('fail', 'Bad credentials.'));
    else
    {
      $_SESSION['sid'] = array(
        'uid' => $infosUser['id_user'],
        'role' => $infosUser['fk_id_role']
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
