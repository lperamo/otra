<?
/** Backend of the LPCMS
 *
 * @author Lionel Péramo */

namespace bundles\CMS\backend\controllers;

use lib\myLibs\Controller,
    lib\myLibs\bdd\Sql,
    lib\myLibs\Session,
    config\Router;

class ajaxStatsController extends Controller
{
  public function preExecute(){
    if($this->action != 'index' && !isset($_SESSION['sid']))
    {
      Router::get('backend');
      die;
    }
  }

  public function indexAction(){
    Sql::getDB();

    // Retrieving the headers
    // $users = $db->fetchAssoc($db->query('SELECT * FROM lpcms_user'));
    // dump($users);

    echo $this->renderView('index.phtml', array(
      'items' => array()
    ), true);
  }
}
?>
