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
use const otra\console\{CLI_INFO_HIGHLIGHT, CLI_SUCCESS, END_COLOR};

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
  MAIN_RESOURCES_PATH = BASE_PATH . 'bundles/resources/',
  MAIN_JS_ROUTING = MAIN_RESOURCES_PATH . 'jsRouting.js',
  ERASE_SEQUENCE = "\033[1A\r\033[K",
  OTRA_SUCCESS = CLI_SUCCESS . '  ✔  ' . END_COLOR;

if (!file_exists(MAIN_RESOURCES_PATH))
  mkdir(MAIN_RESOURCES_PATH);

file_put_contents(
  MAIN_JS_ROUTING,
  'const JS_ROUTING = ' .
  json_encode($routes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK) . PHP_EOL
);

echo ERASE_SEQUENCE, 'JavaScript routing generated in ', CLI_INFO_HIGHLIGHT, MAIN_JS_ROUTING, END_COLOR, OTRA_SUCCESS,
  PHP_EOL;

