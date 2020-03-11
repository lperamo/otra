<?php
/**
 * LPFramework - Core - Errors - 404
 *
 * @author Lionel Péramo */

namespace src\controllers\errors;

use src\Controller;
use config\Routes;

class Error404Action extends Controller
{
  public function error404Action()
  {
//    var_dump(array_search('core', Routes::$_));
    $routes = array_keys(Routes::$_);
    $defaultUrl = null;

    foreach ($routes as &$route)
    {
      if (strpos($route, 'otra') !== false)
        continue;

      $defaultUrl = Routes::$_[$route]['chunks'][0];
      break;
    }

    echo $this->renderView(
      'error404.phtml',
      ['suggestedRoute' => $defaultUrl]
    );
  }
}
?>
