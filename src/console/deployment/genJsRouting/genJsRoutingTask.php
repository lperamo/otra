<?php
/**
 * JavaScript route mapping task
 *
 * @author  Lionel Péramo
 * @package otra\console\deployment
 */
declare(strict_types=1);

namespace otra\console\deployment\genJsRouting;

use otra\config\Routes;
use otra\OtraException;
use const otra\cache\php\BUNDLES_PATH;
use const otra\console\
{CLI_INFO_HIGHLIGHT, CLI_WARNING, ERASE_SEQUENCE, END_COLOR, SUCCESS};

const
  MAIN_RESOURCES_PATH = BUNDLES_PATH . 'resources/js/',
  MAIN_JS_ROUTING = MAIN_RESOURCES_PATH . 'jsRouting.js';

/**
 * @throws OtraException
 * @return void
 */
function genJsRouting(): void
{
  echo 'Generating JavaScript routing...', PHP_EOL;

  // Checks if we have routes to generate
  if (!file_exists(BUNDLES_PATH . 'config/Routes.php'))
  {
    echo CLI_WARNING, 'You don\'t have any routes to generate.', END_COLOR, PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }

  // Preparing the routes
  $routes = Routes::$allRoutes;

  unset(
    $routes['otra_404'],
    $routes['otra_clearSQLLogs'],
    $routes['otra_exception'],
    $routes['otra_profiler'],
    $routes['otra_refreshSQLLogs']
  );

  // For each route, if the url contains curly braces then it has parameters that we have to remove
  foreach ($routes as &$route)
  {
    $routeUrl = $route['chunks'][Routes::ROUTES_CHUNKS_URL];
    $curlyBracePos = mb_strpos($routeUrl, '{');

    if ($curlyBracePos === false)
      continue;

    $route['chunks'][Routes::ROUTES_CHUNKS_URL] = mb_substr($routeUrl, 0, $curlyBracePos);
  }

  // Creating the main js folder if it does not exist
  if (!file_exists(MAIN_RESOURCES_PATH))
    mkdir(MAIN_RESOURCES_PATH, 0777, true);

  // Saving the routes in a JavaScript file
  file_put_contents(
    MAIN_JS_ROUTING,
    'window.JS_ROUTING = ' .
    json_encode($routes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK) . PHP_EOL
  );

  echo ERASE_SEQUENCE, 'JavaScript routing generated in ', CLI_INFO_HIGHLIGHT, MAIN_JS_ROUTING, END_COLOR, SUCCESS;
}
