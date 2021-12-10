<?php
declare(strict_types=1);

namespace src\console\helpAndTools\routes;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\{APP_ENV, BUNDLES_PATH, CONSOLE_PATH, CORE_PATH, DEV};
use const otra\config\VERSION;
use const otra\console\{CLI_ERROR, CLI_INFO, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, END_COLOR};
use function otra\tools\copyFileAndFolders;

/**
 * @runTestsInSeparateProcesses
 */
class RoutesTaskTest extends TestCase
{
  private const
    OTRA_CONSOLE_FILENAME = 'otra.php',
    TASK_ROUTES = 'routes',
    OTRA_TASK_BUILD_DEV = 'buildDev',
    OTRA_TASK_CREATE_HELLO_WORLD = 'createHelloWorld',
    OTRA_TASK_GEN_ASSETS = 'genAssets',
    OTRA_MAIN_BUNDLES_ROUTES_CONFIG = BUNDLES_PATH . 'config/Routes.php',
    PHP_STATUS = '[PHP]',
    LABEL_NO_OTHER_RESOURCES = ' No other resources. ',
    ROUTE_OTRA_404 = 'otra_404',
    ROUTE_OTRA_CLEAR_SQL_LOGS = 'otra_clearSQLLogs',
    ROUTE_OTRA_CSS = 'otra_css',
    ROUTE_OTRA_LOGS = 'otra_logs',
    ROUTE_OTRA_REFRESH_LOGS = 'otra_refreshSQLLogs',
    ROUTE_OTRA_REQUESTS = 'otra_requests',
    ROUTE_OTRA_ROUTES = 'otra_routes',
    ROUTE_OTRA_SQL = 'otra_sql',
    ROUTE_OTRA_TEMPLATE_STRUCTURE = 'otra_templateStructure',
    ROUTE_HELLO_WORLD = 'HelloWorld';


  // it fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  public static function setUpBeforeClass() : void
  {
    parent::setUpBeforeClass();
    // To avoid "Constant otra\console\ADD_BOLD already defined" in this test file
    require_once CONSOLE_PATH . 'colors.php';
  }

  /**
   * Gets the hashed route using SHA1 algorith, enclosed by brackets
   *
   * @param string $route
   *
   * @return string
   */
  private static function getShaRoute(string $route) : string
  {
    return '[' . sha1('ca' . $route . VERSION . 'che') . ']';
  }

  /**
   * @param string $color
   * @param string $route
   * @param string $url
   * @param string $path
   * @param string $status
   * @param string $resources
   *
   * @param bool   $endingLine
   *
   * @return string
   */
  private static function showRouteInformations(
    string $color,
    string $route,
    string $url,
    string $path,
    string $status,
    string $resources,
    bool   $endingLine
  ) : string
  {
    return $color .
      sprintf('%-' . WIDTH_LEFT . 's', $route) .
      str_pad('Url', WIDTH_MIDDLE) . ': ' . $url .  PHP_EOL .
      str_pad(' ', WIDTH_LEFT) .
      str_pad('Path', WIDTH_MIDDLE) . ': ' . $path .
      PHP_EOL .
      str_pad(' ', WIDTH_LEFT) .
      str_pad('Resources', WIDTH_MIDDLE) . ': ' . CLI_SUCCESS . $status . $color .
      $resources . PHP_EOL .
      ($endingLine ? END_COLOR . str_repeat('-', WIDTH_LEFT + WIDTH_MIDDLE + WIDTH_RIGHT) . PHP_EOL : '');
  }

  /**
   * @throws OtraException
   *
   * @author Lionel Péramo
   */
  public function testRoutes() : void
  {
    define(__NAMESPACE__ . '\\WIDTH_LEFT', 25);
    define(__NAMESPACE__ . '\\WIDTH_MIDDLE', 10);
    define(__NAMESPACE__ . '\\WIDTH_RIGHT', 70);

    // context
    $tasksClassMap = require TASK_CLASS_MAP_PATH;
    $_SERVER[APP_ENV] = DEV;

    require CORE_PATH . 'tools/copyFilesAndFolders.php';

    copyFileAndFolders(
      [CONSOLE_PATH . 'architecture/starters/helloWorld/Routes.php'],
      [self::OTRA_MAIN_BUNDLES_ROUTES_CONFIG]
    );

    // ob_start to avoid showing output unrelated to what we want to test
    ob_start();
    TasksManager::execute(
      $tasksClassMap,
      self::OTRA_TASK_CREATE_HELLO_WORLD,
      [self::OTRA_CONSOLE_FILENAME, self::OTRA_TASK_CREATE_HELLO_WORLD]
    );
    TasksManager::execute(
      $tasksClassMap,
      self::OTRA_TASK_BUILD_DEV,
      [
        self::OTRA_CONSOLE_FILENAME,
        'genBootstrap'
      ]
    );
    TasksManager::execute(
      $tasksClassMap,
      self::OTRA_TASK_GEN_ASSETS,
      [self::OTRA_CONSOLE_FILENAME, self::OTRA_TASK_GEN_ASSETS]
    );
    ob_end_clean();

    // testing
    $this->expectOutputString(
      self::showRouteInformations(
        CLI_INFO_HIGHLIGHT,
        self::ROUTE_OTRA_404,
        '/404',
        '/otra/errorsController/error404Action',
        self::PHP_STATUS,
        self::LABEL_NO_OTHER_RESOURCES . self::getShaRoute(self::ROUTE_OTRA_404),
        true
      ) .
      self::showRouteInformations(
        CLI_INFO,
        self::ROUTE_OTRA_CLEAR_SQL_LOGS,
        '/dbg/clearSQLLogs',
        '/otra/profilerController/clearSQLLogsAction',
        self::PHP_STATUS,
        self::LABEL_NO_OTHER_RESOURCES . self::getShaRoute(self::ROUTE_OTRA_CLEAR_SQL_LOGS),
        true
      ) .
      self::showRouteInformations(
        CLI_INFO_HIGHLIGHT,
        self::ROUTE_OTRA_CSS,
        '/profiler/css',
        '/otra/profilerController/cssAction',
        self::PHP_STATUS,
        CLI_SUCCESS . '[SCREEN CSS]' . CLI_INFO_HIGHLIGHT . CLI_ERROR . '[PRINT CSS]' . CLI_INFO_HIGHLIGHT .
        self::getShaRoute(self::ROUTE_OTRA_CSS),
        true
      ) .
      self::showRouteInformations(
        CLI_INFO,
        self::ROUTE_OTRA_LOGS,
        '/profiler/logs',
        '/otra/profilerController/logsAction',
        self::PHP_STATUS,
        CLI_SUCCESS . '[SCREEN CSS]' . CLI_INFO . CLI_ERROR . '[PRINT CSS]' . CLI_INFO .
        self::getShaRoute(self::ROUTE_OTRA_LOGS),
        true
      ) .
      self::showRouteInformations(
        CLI_INFO_HIGHLIGHT,
        self::ROUTE_OTRA_REFRESH_LOGS,
        '/dbg/refreshSQLLogs',
        '/otra/profilerController/refreshSQLLogsAction',
        self::PHP_STATUS,
        self::LABEL_NO_OTHER_RESOURCES . self::getShaRoute(self::ROUTE_OTRA_REFRESH_LOGS),
        true
      ) .
      self::showRouteInformations(
        CLI_INFO,
        self::ROUTE_OTRA_REQUESTS,
        '/profiler/requests',
        '/otra/profilerController/requestsAction',
        self::PHP_STATUS,
        CLI_SUCCESS. '[SCREEN CSS]' . CLI_INFO . CLI_ERROR . '[PRINT CSS]' . CLI_INFO .
        self::getShaRoute(self::ROUTE_OTRA_REQUESTS),
        true
      ) .
      self::showRouteInformations(
        CLI_INFO_HIGHLIGHT,
        self::ROUTE_OTRA_ROUTES,
        '/profiler/routes',
        '/otra/profilerController/routesAction',
        self::PHP_STATUS,
        CLI_SUCCESS. '[SCREEN CSS]' . CLI_INFO_HIGHLIGHT . CLI_ERROR . '[PRINT CSS]' . CLI_INFO_HIGHLIGHT .
        self::getShaRoute(self::ROUTE_OTRA_ROUTES),
        true
      ) .
      self::showRouteInformations(
        CLI_INFO,
        self::ROUTE_OTRA_SQL,
        '/profiler/sql',
        '/otra/profilerController/sqlAction',
        self::PHP_STATUS,
        CLI_SUCCESS. '[SCREEN CSS]' . CLI_INFO . CLI_ERROR . '[PRINT CSS]' . CLI_INFO . CLI_SUCCESS . '[JS]' .
        CLI_INFO . self::getShaRoute(self::ROUTE_OTRA_SQL),
        true
      ) .
      self::showRouteInformations(
        CLI_INFO_HIGHLIGHT,
        self::ROUTE_OTRA_TEMPLATE_STRUCTURE,
        '/profiler/templateStructure',
        '/otra/profilerController/templateStructureAction',
        self::PHP_STATUS,
        CLI_SUCCESS. '[SCREEN CSS]' . CLI_INFO_HIGHLIGHT . CLI_ERROR . '[PRINT CSS]' . CLI_INFO_HIGHLIGHT .
        self::getShaRoute(self::ROUTE_OTRA_TEMPLATE_STRUCTURE),
        true
      ) .
      self::showRouteInformations(
        CLI_INFO,
        self::ROUTE_HELLO_WORLD,
        '/helloworld',
        'HelloWorld/frontend/indexController/HomeAction',
        '[SCREEN CSS]' . CLI_INFO . CLI_SUCCESS . '[PRINT CSS]' . CLI_INFO . CLI_SUCCESS .
          '[TEMPLATE]',
        self::getShaRoute(self::ROUTE_HELLO_WORLD),
        false
      ) . END_COLOR
    );

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TASK_ROUTES,
      [self::OTRA_CONSOLE_FILENAME, self::TASK_ROUTES]
    );
  }
}
