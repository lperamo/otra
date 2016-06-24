<?
/**
 * LPCMS - Backend - Index - Stats
 *
 * @author Lionel Péramo */

namespace bundles\CMS\backend\controllers\index;

use lib\myLibs\{Controller, bdd\Sql};
use \bundles\CMS\services\backendService;

class statsAction extends Controller
{
  public function statsAction()
  {
    backendService::checkConnection($this->action);
    Sql::getDB();

    // Retrieving the headers
    // $users = $db->fetchAssoc($db->query('SELECT * FROM lpcms_user'));
    // dump($users);

    echo $this->renderView('stats.phtml', ['items' => []]);
  }
}
?>
