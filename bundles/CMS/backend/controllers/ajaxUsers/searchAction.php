<?
/**
 * LPCMS - Backend - AjaxUsers - Delete
 *
 * @author Lionel Péramo
 */

namespace bundles\CMS\backend\controllers\ajaxUsers;

use lib\myLibs\{Controller, bdd\Sql};
use bundles\CMS\{models\User, services\backendService, services\usersService};

class searchAction extends Controller
{
  /** A criteria based search or a previous/next pagination */
  public function searchAction()
  {
    backendService::checkConnection($this->action);
    usersService::securityCheck();

    // TODO ip to ban
    if (false === isset($_POST['type'], $_POST['mail'], $_POST['pseudo'], $_POST['role'], $_POST['limit'], $_POST['prev'], $_POST['last']) || 7 < count($_POST))
    {
      echo '{"success": false, "msg": "Hack."}';
      return;
    }


    $db = Sql::getDB();

    $data = User::search($_POST);
    $users = (true === isset($data[0])) ? $data[0] : $data;

    if (false === empty($users))
    {
      $users = $db->values($users);
      sort($users);

      if (true === empty($users))
        echo '{"success": true, "msg": "", "count": 0, "first": 0, "last":0}';
      else
      {
        end($users);
        $last = current($users);
        reset($users);

        echo '{"success": true, "msg":' . json_encode($this->renderView(
            'singleUser.phtml',
            ['users' => $users],
            true)
          ) . ', "count":' . (true === isset($data[1]) ? $data[1] : '-1') .
          ', "first":' . $users[0]['id_user'] .
          ', "last":' . $last['id_user'] . '}';
      }
    } else
      echo '{"success": true, "msg": ""}';

    return;
  }
}
?>
