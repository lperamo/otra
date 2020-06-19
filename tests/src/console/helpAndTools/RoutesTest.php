<?php
declare(strict_types=1);

namespace src\console\helpAndTools;

use otra\console\TasksManager;
use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class RoutesTest extends TestCase
{
  private const
    TASKS_CLASSMAP_FILENAME = 'tasksClassMap.php',
    TASKS_CLASS_MAP = BASE_PATH . 'cache/php/' . self::TASKS_CLASSMAP_FILENAME,
    TASK_ROUTES = 'routes',
    OTRA_TASK_HELP = 'help',
    OTRA_TASK_CREATE_HELLO_WORLD = 'createHelloWorld',
    OTRA_TASK_GEN_ASSETS = 'genAssets',
    OTRA_MAIN_BUNDLES_ROUTES_CONFIG = BASE_PATH . 'bundles/config/Routes.php';

  /**
   * @param string $parameter
   * @param string $description
   * @param string $requiredOrOptional 'required' or 'optional'
   *
   * @return string
   */
  private static function taskParameter(string $parameter, string $description, string $requiredOrOptional) : string
  {
    return CLI_LIGHT_CYAN . '   + ' .
      str_pad($parameter, TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) . CLI_LIGHT_GRAY . ': ' .
      CLI_LIGHT_CYAN . '(' . $requiredOrOptional . ') ' . CLI_CYAN . $description . PHP_EOL;
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
      str_pad('Url', WIDTH_MIDDLE, ' ') . ': ' . $url .  PHP_EOL .
      str_pad(' ', WIDTH_LEFT, ' ') .
      str_pad('Path', WIDTH_MIDDLE, ' ') . ': ' . $path .
      PHP_EOL .
      str_pad(' ', WIDTH_LEFT, ' ') .
      str_pad('Resources', WIDTH_MIDDLE, ' ') . ': ' . CLI_LIGHT_GREEN . $status . $color .
      $resources . PHP_EOL .
      ($endingLine ? END_COLOR . str_repeat('-', WIDTH_LEFT + WIDTH_MIDDLE + WIDTH_RIGHT) . PHP_EOL : '');
  }

  /**
   * @throws \otra\OtraException
   *
   * @author Lionel Péramo
   */
  public function testRoutes() : void
  {
    define('WIDTH_LEFT', 25);
    define('WIDTH_MIDDLE', 10);
    define('WIDTH_RIGHT', 70);

    // context
    $tasksClassMap = require BASE_PATH . 'cache/php/tasksClassMap.php';
    $_SERVER['APP_ENV'] = 'dev';

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
      ['otra.php', self::OTRA_TASK_CREATE_HELLO_WORLD]
    );

    TasksManager::execute(
      $tasksClassMap,
      self::OTRA_TASK_GEN_ASSETS,
      ['otra.php', self::OTRA_TASK_GEN_ASSETS]
    );
    ob_end_clean();

    // testing
    $this->expectOutputString(
      self::showRouteInformations(
        CLI_LIGHT_CYAN,
        'otra_refreshSQLLogs',
        '/dbg/refreshSQLLogs',
        '/otra/profilerController/refreshSQLLogsAction',
        '[PHP]',
        ' No other resources. [aaf5b99241b8ca17500e63bcdc7fe2c1fd45daf1]',
        true
      ) .
      self::showRouteInformations(
        CLI_CYAN,
        'otra_clearSQLLogs',
        '/dbg/clearSQLLogs',
        '/otra/profilerController/clearSQLLogsAction',
        '[PHP]',
        ' No other resources. [f12d8cd7ca8ed48da320a5674ea6740828aafed5]',
        true
      ) .
      self::showRouteInformations(
        CLI_LIGHT_CYAN,
        'otra_profiler',
        '/dbg',
        '/otra/profilerController/indexAction',
        '[PHP]',
        ' No other resources. [1ddb0cc7fe3de4d3ea1d25c640264572b78f302e]',
        true
      ) .
      self::showRouteInformations(
        CLI_CYAN,
        'otra_404',
        '/404',
        '/otra/errorsController/error404Action',
        '[PHP]',
        ' No other resources. [f034aa32d95877a154a0f6aed106d25ab50d1370]',
        true
      ) .
      self::showRouteInformations(
        CLI_LIGHT_CYAN,
        'HelloWorld',
        '/helloworld',
        'HelloWorld/frontend/indexController/HomeAction',
        '[TEMPLATE]',
        '[f40ed5d7e08cd2a4a1b8fe2b5c4191722f35fae2]',
        false
      ) . END_COLOR
    );

    // launching
    TasksManager::execute(
      $tasksClassMap,
      self::TASK_ROUTES,
      ['otra.php', self::TASK_ROUTES]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testRoutesHelp()
  {
    $this->expectOutputString(
      CLI_WHITE .
      str_pad(self::TASK_ROUTES, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_LIGHT_GRAY . ': ' . CLI_CYAN .
      'Shows the routes and their associated kind of resources in the case they have some. (lightGreen whether they exists, red otherwise)' .
      PHP_EOL .
      self::taskParameter(
        'route',
        'The name of the route that we want information from, if we wish only one route description.',
        TasksManager::OPTIONAL_PARAMETER
      ) . END_COLOR
    );

    TasksManager::execute(
      require self::TASKS_CLASS_MAP,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::TASK_ROUTES]
    );
  }
}
