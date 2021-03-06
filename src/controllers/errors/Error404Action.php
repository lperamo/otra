<?php
declare(strict_types=1);
/**
 * LPFramework - Core - Errors - 404
 *
 * @author Lionel Péramo */

namespace otra\controllers\errors;

use otra\Controller;
use config\Routes;

/**
 * @package otra\controllers\errors
 */
class Error404Action extends Controller
{
  /**
   * @param array $baseParams
   * @param array $getParams
   *
   * @throws \otra\OtraException
   */
  public function __construct(array $baseParams = [], array $getParams = [])
  {
    parent::__construct($baseParams, $getParams);
    $routes = array_keys(Routes::$_);
    $defaultUrl = null;

    foreach ($routes as $route)
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

