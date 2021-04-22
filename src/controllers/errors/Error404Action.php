<?php
declare(strict_types=1);
namespace otra\controllers\errors;

use otra\Controller;
use config\Routes;

/**
 * @author Lionel Péramo
 * @package otra\controllers\errors
 */
class Error404Action extends Controller
{
  /**
   * @param array $otraParams
   * @param array $params
   *
   * @throws \otra\OtraException
   */
  public function __construct(array $otraParams = [], array $params = [])
  {
    parent::__construct($otraParams, $params);
    $routes = array_keys(Routes::$allRoutes);
    $defaultUrl = null;

    foreach ($routes as $route)
    {
      if (str_contains($route, 'otra'))
        continue;

      $defaultUrl = Routes::$allRoutes[$route]['chunks'][0];
      break;
    }

    echo $this->renderView(
      'error404.phtml',
      ['suggestedRoute' => $defaultUrl]
    );
  }
}

