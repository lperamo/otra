<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\deployment
 */
declare(strict_types=1);

namespace otra\console\deployment\genBootstrap;

use FilesystemIterator;
use otra\OtraException;
use otra\config\{AllConfig,Routes};
use const otra\bin\CACHE_PHP_INIT_PATH;
use const otra\cache\php\{APP_ENV, BASE_PATH, BUNDLES_PATH, CONSOLE_PATH, CORE_PATH, PROD};
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_INFO, CLI_INFO_HIGHLIGHT, CLI_WARNING, END_COLOR};
use function otra\console\deployment\genClassMap\genClassMap;
use function otra\tools\{cliCommand, files\compressPHPFile, guessRoute};

const
GEN_BOOTSTRAP_ARG_CLASS_MAPPING = 2,
GEN_BOOTSTRAP_ARG_VERBOSE = 3,
GEN_BOOTSTRAP_ARG_LINT = 4,
GEN_BOOTSTRAP_ARG_ROUTE = 5,
OTRA_KEY_DRIVER = 'driver',
BOOTSTRAP_PATH = BASE_PATH . 'cache/php',
ROUTE_MANAGEMENT_TEMPORARY_FILE = CACHE_PHP_INIT_PATH . 'RouteManagement_.php';

/**
 * @param array $argumentsVector
 *
 * @throws OtraException
 * @return void|int
 */
function genBootstrap(array $argumentsVector)
{
  if (!file_exists(BUNDLES_PATH) || !(new FilesystemIterator(BUNDLES_PATH))->valid())
  {
    echo CLI_ERROR, 'There are no bundles to use!', END_COLOR, PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }

  define(__NAMESPACE__ . '\\GEN_BOOTSTRAP_LINT', isset($argumentsVector[GEN_BOOTSTRAP_ARG_LINT]) && $argumentsVector[GEN_BOOTSTRAP_ARG_LINT] === '1');
  define(__NAMESPACE__ . '\\VERBOSE', isset($argumentsVector[GEN_BOOTSTRAP_ARG_VERBOSE]) ? (int) $argumentsVector[GEN_BOOTSTRAP_ARG_VERBOSE] : 0);

  // We do not do micro bootstraps for 'otra_exception' route.
  if (isset($argumentsVector[GEN_BOOTSTRAP_ARG_ROUTE]) && $argumentsVector[GEN_BOOTSTRAP_ARG_ROUTE] === 'otra_exception')
  {
    echo CLI_WARNING, 'We do not do micro bootstraps for the route ', CLI_INFO_HIGHLIGHT, 'otra_exception',
    CLI_WARNING, '.', END_COLOR, PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }

  if (!isset(AllConfig::$deployment) || !isset(AllConfig::$deployment['domainName']))
  {
    echo CLI_ERROR, 'You must define the ', CLI_INFO_HIGHLIGHT, 'domainName', CLI_ERROR,
    ' key in the production configuration file to make this task work.', END_COLOR, PHP_EOL, PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }

  // We generate the class mapping file if we need it.
  if (!(isset($argumentsVector[GEN_BOOTSTRAP_ARG_CLASS_MAPPING]) && '0' === $argumentsVector[GEN_BOOTSTRAP_ARG_CLASS_MAPPING]))
  {
    // Generation of the class mapping
    require CONSOLE_PATH . 'deployment/genClassMap/genClassMapTask.php';
    genClassMap([]);

    // Re-execute our task now that we have a correct class mapping
    require CORE_PATH . 'tools/cli.php';

    [$status, $return] = cliCommand(
      PHP_BINARY . ' ./bin/otra.php genBootstrap 0 ' . VERBOSE . ' ' . (int) GEN_BOOTSTRAP_LINT .
      ' ' . ($argumentsVector[GEN_BOOTSTRAP_ARG_ROUTE] ?? '')
    );
    echo $return;

    return $status;
  }

  require BASE_PATH . 'config/Routes.php';
  require CORE_PATH . 'Router.php';

  // Checks that the folder of micro bootstraps exists
  if (!file_exists(BOOTSTRAP_PATH))
    mkdir(BOOTSTRAP_PATH);

  // Checks whether we want only one/many CORRECT route(s)
  if (isset($argumentsVector[GEN_BOOTSTRAP_ARG_ROUTE]))
  {
    require CORE_PATH . 'tools/guessRoute.php';
    $route = guessRoute($argumentsVector[GEN_BOOTSTRAP_ARG_ROUTE]);
    $routes = [$route => Routes::$allRoutes[$route]];
    echo 'Generating \'micro\' bootstrap ...', PHP_EOL, PHP_EOL;
  } else
  {
    $routes = Routes::$allRoutes;
    // otra_exception route has no controller
    unset($routes['otra_exception']);
    echo 'Generating \'micro\' bootstraps for the routes ...', PHP_EOL, PHP_EOL;
  }

  // In CLI mode, the $_SERVER variable is not set, so we set it !
  $_SERVER[APP_ENV] = PROD;

  // CACHE_PATH will not be found if we do not have dbConnections in AllConfig, so we need to explicitly include the
  // configuration. We check if we do not have already loaded the configuration before.
  define(
    __NAMESPACE__ . '\\PATH_CONSTANTS',
    [
      'externalConfigFile' => BUNDLES_PATH . 'config/Config.php',
      OTRA_KEY_DRIVER => !empty(AllConfig::$dbConnections)
      && array_key_exists(OTRA_KEY_DRIVER, AllConfig::$dbConnections[key(AllConfig::$dbConnections)])
        ? AllConfig::$dbConnections[key(AllConfig::$dbConnections)][OTRA_KEY_DRIVER]
        : '',
      "_SERVER[APP_ENV]" => $_SERVER[APP_ENV],
      'temporaryEnv' => PROD
    ]
  );

  // We load all the libraries needed to generate bootstraps
  require CONSOLE_PATH . 'deployment/genBootstrap/oneBootstrap.php';
  require CONSOLE_PATH . 'deployment/genBootstrap/taskFileOperation.php';
  require CORE_PATH . 'tools/files/compressPhpFile.php';
  require CORE_PATH . 'Session.php';
  require CORE_PATH . 'bdd/Sql.php';

  foreach(array_keys($routes) as $routeKey => $route)
  {
    if (array_key_first($routes) !== $routeKey)
      echo PHP_EOL;

    if (isset($routes[$route]['resources']['template']) && $routes[$route]['resources']['template'] === true)
      echo CLI_BASE, str_pad(str_pad(' ' . $route, 25) . CLI_INFO
        . ' [NO MICRO BOOTSTRAP => TEMPLATE GENERATED] ' . CLI_BASE, 94, '=', STR_PAD_BOTH),
        END_COLOR, PHP_EOL;
    else
      oneBootstrap($route);
  }

  // Final specific management for routes files
  echo 'Create the specific routes management file... ', PHP_EOL;

  $fileToInclude = CORE_PATH . 'Router.php';

  contentToFile(
    fixFiles(
      $routes[$route]['chunks'][Routes::ROUTES_CHUNKS_BUNDLE],
      $route,
      file_get_contents($fileToInclude) . PHP_END_TAG_STRING,
      VERBOSE,
      $fileToInclude
    ),
    ROUTE_MANAGEMENT_TEMPORARY_FILE
  );

  if (GEN_BOOTSTRAP_LINT && hasSyntaxErrors(ROUTE_MANAGEMENT_TEMPORARY_FILE))
    return 1;

  compressPHPFile(ROUTE_MANAGEMENT_TEMPORARY_FILE, CACHE_PHP_INIT_PATH . 'RouteManagement.php');
  unlink(ROUTE_MANAGEMENT_TEMPORARY_FILE);

  echo PHP_EOL;
}
