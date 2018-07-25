<?
/**
 * LPCMS - Backend - Index - Stats
 *
 * @author Lionel Péramo */

namespace bundles\CMS\backend\controllers\index;

use lib\myLibs\{Controller, bdd\Sql};
use bundles\CMS\services\backendService;

class statsAction extends Controller
{
  public function statsAction()
  {
    if (backendService::checkConnection($this->route) === false)
      return false;

    Sql::getDB();

    // Retrieving the headers
    // $users = $db->fetchAssoc($db->query('SELECT * FROM lpcms_user'));
    // dump($users);

    echo $this->renderView('stats.phtml', ['items' => []]);
  }
}
?>
