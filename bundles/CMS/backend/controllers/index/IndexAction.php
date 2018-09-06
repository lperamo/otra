<?
/**
 * LPCMS - Backend - Index - Index
 *
 * @author Lionel Péramo */

namespace bundles\CMS\backend\controllers\index;

use bundles\CMS\models\{Footer, Header};
use lib\myLibs\{Controller, bdd\Sql, Router};
use bundles\CMS\services\BackendService;

class indexAction extends Controller
{
  /**
   * Shows a login form or the backend modules page if the user is already connected
   */
  public function indexAction()
  {
    if (true === isset($_SESSION['sid']))
    {
      Router::get('backendModules');
      return;
    }

    BackendService::checkConnection($this->action);

    Sql::getDB();

    $_SESSION['headers'] = Header::getAll();
    $_SESSION['footers'] = Footer::getAll();

    echo $this->renderView('index.phtml', [
      'headers' => $_SESSION['headers'],
      'footers' => $_SESSION['footers'],
      'lpcms_body' => 'page_t.jpg'
    ]);
  }
}
?>
