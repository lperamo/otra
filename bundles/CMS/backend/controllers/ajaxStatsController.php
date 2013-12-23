<?php
/** Backend of the LPCMS
 *
 * @author Lionel Péramo */

namespace bundles\CMS\backend\controllers;

use lib\myLibs\core\Controller,
    lib\myLibs\core\bdd\Sql,
    lib\myLibs\core\Session,
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
    $db = Session::get('dbConn');
    $db->selectDb();

    // Puts the UTF-8 encoding in order to correctly render accents
    $db->query('SET NAMES UTF8');

    // Retrieving the headers
    // $users = $db->fetchAssoc($db->query('SELECT * FROM lpcms_user'));
    // dump($users);

    echo $this->renderView('index.phtml', array(
      'items' => array()
    ), true);
  }
}
?>
