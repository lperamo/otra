<?php
declare(strict_types=1);
namespace otra\controllers\errors;

use otra\Controller;
use otra\config\Routes;
use otra\OtraException;

/**
 * @author Lionel Péramo
 * @package otra\controllers\errors
 */
class Error404Action extends Controller
{
  /**
   * @param array $otraParams [pattern, bundle, module, controller, action, route, js, css, internalRedirect]
   * @param array $params     [...getParams, ...postParams, etc.]
   *
   * @throws OtraException
   */
  public function __construct(array $otraParams = [], array $params = [])
  {
    parent::__construct($otraParams, $params);
    $routes = array_keys(Routes::$allRoutes);
    $defaultUrl = null;

    foreach ($routes as $route)
    {
      if (str_contains($route, 'otra')
        || str_contains(Routes::$allRoutes[$route]['chunks'][Routes::ROUTES_CHUNKS_URL], '{'))
        continue;

      $defaultUrl = Routes::$allRoutes[$route]['chunks'][Routes::ROUTES_CHUNKS_URL];
      break;
    }

    echo $this->renderView(
      'error404.phtml',
      ['suggestedRoute' => $defaultUrl]
    );
  }
}
