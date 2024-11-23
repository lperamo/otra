<?php
declare(strict_types=1);

namespace src\console\deployment\genAssets;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\BASE_PATH;
use const otra\config\VERSION;
use const otra\console\
{CLI_ERROR, CLI_GRAY, CLI_INFO, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, CLI_WARNING, END_COLOR};

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class GenAssetsTaskTest extends TestCase
{
  private const int
    OTRA_HELLO_WORLD_MAX_ROUTES_NUMBER = 11,
    ROUTES_PADDING = 25;

  private const string
    OTRA_BINARY = 'otra.php',
    OTRA_TASK_CREATE_HELLO_WORLD = 'createHelloWorld',
    OTRA_TASK_GEN_ASSETS = 'genAssets',
    OTRA_TASK_INIT = 'init',
    
    LABEL_SCREEN_CSS = 'SCREEN CSS';

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function test_noBundles() : void
  {
    // context

    // testing
    self::expectException(OtraException::class);
    self::expectOutputString(CLI_ERROR . 'There are no bundles to use!' . END_COLOR . PHP_EOL);

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_GEN_ASSETS,
      [self::OTRA_BINARY, self::OTRA_TASK_GEN_ASSETS]
    );
  }

  /**
   * @medium
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function test_ok() : void
  {
    // context
    ob_start();
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_INIT,
      [self::OTRA_BINARY, self::OTRA_TASK_INIT]
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CREATE_HELLO_WORLD,
      [self::OTRA_BINARY, self::OTRA_TASK_CREATE_HELLO_WORLD]
    );
    ob_end_clean();

    // testing
    $outputExpected = PHP_EOL . 'Cleaning the resources cache...' . CLI_SUCCESS . ' OK' . END_COLOR . PHP_EOL .
      self::OTRA_HELLO_WORLD_MAX_ROUTES_NUMBER . ' routes to process. Processing the routes ...' . PHP_EOL . PHP_EOL;

    $routesToTest = [
      'otra_404',
      'otra_clearSQLLogs',
      'otra_css',
      'otra_exception',
      'otra_logs',
      'otra_refreshSQLLogs',
      'otra_requests',
      'otra_routes',
      'otra_sql',
      'otra_templateStructure',
      'HelloWorld'
    ];

    foreach ($routesToTest as $route)
    {
      $outputExpected .= CLI_INFO_HIGHLIGHT . str_pad($route, self::ROUTES_PADDING) . CLI_GRAY;

      if ($route === 'otra_exception')
        $outputExpected .= ' [NOTHING TO DO (NOT IMPLEMENTED FOR THIS PARTICULAR ROUTE)]';
      elseif (in_array(
        $route,
        ['otra_404', 'otra_clearSQLLogs', 'otra_exception', 'otra_refreshSQLLogs']
      ))
        $outputExpected .= ' [' . CLI_INFO . 'Nothing to do' . CLI_GRAY . '] =>' . CLI_SUCCESS . ' OK' . END_COLOR;
      elseif (in_array($route, ['otra_css', 'otra_logs', 'otra_requests', 'otra_routes', 'otra_templateStructure']))
        $outputExpected .= ' [' . CLI_SUCCESS . self::LABEL_SCREEN_CSS . CLI_GRAY . ']' .
          ' [' . CLI_ERROR . 'NO PRINT CSS' . CLI_GRAY . ']' .
          ' [' . CLI_INFO . 'NO JS' . CLI_GRAY . ']' .
          ' [' . CLI_INFO . 'NO TEMPLATE' . CLI_GRAY . '] => ' . CLI_SUCCESS . 'OK' . END_COLOR;
      elseif ($route === 'otra_sql')
        $outputExpected .= ' [' . CLI_SUCCESS . self::LABEL_SCREEN_CSS . CLI_GRAY . ']' .
          ' [' . CLI_ERROR . 'NO PRINT CSS' . CLI_GRAY . ']' .
          ' [' . CLI_SUCCESS . 'JS' . CLI_GRAY . ']' .
          ' [' . CLI_INFO . 'NO TEMPLATE' . CLI_GRAY . '] => ' . CLI_SUCCESS . 'OK' . END_COLOR;
      else
        $outputExpected .= ' [' . CLI_SUCCESS . self::LABEL_SCREEN_CSS . CLI_GRAY . ']' .
          ' [' . CLI_SUCCESS . 'PRINT CSS' . CLI_GRAY . ']' .
          ' [' . CLI_INFO . 'NO JS' . CLI_GRAY . ']' .
          ' [' . CLI_SUCCESS . 'TEMPLATE' . CLI_GRAY . '] => ' . CLI_SUCCESS . 'OK' . END_COLOR;

      $outputExpected .= '[' . CLI_INFO . sha1('ca' . $route . VERSION . 'che') . END_COLOR .
        ']' . PHP_EOL;
    }

    self::expectOutputString(
      $outputExpected .
      CLI_ERROR . 'The JSON manifest file ' . CLI_WARNING . BASE_PATH . 'web/devManifest.json' . CLI_ERROR .
      ' to optimize does not exist.' . END_COLOR . PHP_EOL .
      'Checking for uncompressed SVGs in the folder ' . CLI_INFO_HIGHLIGHT . BASE_PATH . 'web/images' . END_COLOR .
      ' ...' . PHP_EOL .
      CLI_WARNING . 'There is no folder ' . CLI_INFO_HIGHLIGHT . BASE_PATH . 'web/images' . CLI_WARNING . '.' .
      END_COLOR . PHP_EOL);

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_GEN_ASSETS,
      [self::OTRA_BINARY, self::OTRA_TASK_GEN_ASSETS]
    );
  }
}
