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
use const otra\cache\php\BUNDLES_PATH;
use const otra\console\{CLI_INFO_HIGHLIGHT, CLI_SUCCESS, ERASE_SEQUENCE, END_COLOR, SUCCESS};

echo 'Generating JavaScript routing...', PHP_EOL;

$routes = Routes::$allRoutes;

unset(
  $routes['otra_404'],
  $routes['otra_clearSQLLogs'],
  $routes['otra_exception'],
  $routes['otra_profiler'],
  $routes['otra_refreshSQLLogs']
);

const
  MAIN_RESOURCES_PATH = BUNDLES_PATH . 'resources/',
  MAIN_JS_ROUTING = MAIN_RESOURCES_PATH . 'jsRouting.js';

if (!file_exists(MAIN_RESOURCES_PATH))
  mkdir(MAIN_RESOURCES_PATH);

file_put_contents(
  MAIN_JS_ROUTING,
  'const JS_ROUTING = ' .
  json_encode($routes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK) . PHP_EOL
);

echo ERASE_SEQUENCE, 'JavaScript routing generated in ', CLI_INFO_HIGHLIGHT, MAIN_JS_ROUTING, END_COLOR, SUCCESS;

