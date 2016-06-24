<?
/**
 * LPCMS - Backend - Index - Users
 *
 * @author Lionel Péramo */

namespace bundles\CMS\backend\controllers\index;

use lib\myLibs\{Controller, bdd\Sql, Session, Router};
use \bundles\CMS\models\{Header, Footer, Module};
use \bundles\CMS\services\{configService, usersService};

class usersAction extends Controller
{
  public function usersAction()
  {
    echo $this->renderView('users.phtml', usersService::getUsersTab());
  }
}
?>
