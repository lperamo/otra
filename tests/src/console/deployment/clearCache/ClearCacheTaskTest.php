<?php
declare(strict_types=1);

namespace src\console\deployment\clearCache;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\{APP_ENV, BUNDLES_PATH, CACHE_PATH, CONSOLE_PATH, PROD, TEST_PATH};
use const otra\config\VERSION;
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR};
use function otra\console\helpAndTools\generateTaskMetadata\generateTaskMetadata;

/**
 * @runTestsInSeparateProcesses
 */
class ClearCacheTaskTest extends TestCase
{
  private const
    OTRA_TASK_CLEAR_CACHE = 'clearCache',
    OTRA_TASK_GEN_CLASS_MAP = 'genClassMap',
    OTRA_BINARY = 'otra.php',
    MASK_WRONG = -1,
    MASK_INTERNAL_CACHE = 1,
    MASK_BOOTSTRAPS = 2,
    MASK_CSS = 4,
    MASK_JS = 8,
    MASK_TEMPLATES = 16,
    MASK_ROUTE_MANAGEMENT = 32,
    MASK_CLASS_MAPPING = 64,
    MASK_CONSOLE_TASKS_METADATA = 128,
    MASK_SECURITY = 256,
    MASK_ALL = 511,
    CACHE_PHP_PATH = CACHE_PATH . 'php/',
    CACHE_PHP_INIT_PATH = self::CACHE_PHP_PATH . 'init/',
    TEST_ROUTE = 'testTest',
    TEST_ROUTE_BOOTSTRAP = self::CACHE_PHP_PATH . self::TEST_ROUTE . '.php',
    CACHE_PHP_OTRA_ROUTES_PATH = self::CACHE_PHP_PATH . 'otraRoutes/',
    OTRA_404 = self::CACHE_PHP_OTRA_ROUTES_PATH . 'otra_404.php',
    OTRA_CLEAR_SQL_LOGS = self::CACHE_PHP_OTRA_ROUTES_PATH . 'otra_clearSQLLogs.php',
    OTRA_CSS = self::CACHE_PHP_OTRA_ROUTES_PATH . 'otra_css.php',
    OTRA_LOGS = self::CACHE_PHP_OTRA_ROUTES_PATH . 'otra_logs.php',
    OTRA_REFRESH_SQL_LOGS = self::CACHE_PHP_OTRA_ROUTES_PATH . 'otra_refreshSQLLogs.php',
    OTRA_REQUESTS = self::CACHE_PHP_OTRA_ROUTES_PATH . 'otra_requests.php',
    OTRA_ROUTES = self::CACHE_PHP_OTRA_ROUTES_PATH . 'otra_routes.php',
    OTRA_SQL = self::CACHE_PHP_OTRA_ROUTES_PATH . 'otra_sql.php',
    OTRA_TEMPLATE_STRUCTURE = self::CACHE_PHP_OTRA_ROUTES_PATH . 'otra_templateStructure.php',
    BOOTSTRAPS = [
      self::TEST_ROUTE_BOOTSTRAP,
      self::OTRA_404,
      self::OTRA_CLEAR_SQL_LOGS,
      self::OTRA_CSS,
      self::OTRA_LOGS,
      self::OTRA_REFRESH_SQL_LOGS,
      self::OTRA_REQUESTS,
      self::OTRA_ROUTES,
      self::OTRA_SQL,
      self::OTRA_TEMPLATE_STRUCTURE,
    ],
    TEST_CACHE = CACHE_PATH . 'test.cache',
    BUNDLES_CONFIG_PATH = BUNDLES_PATH . 'config/',
    TEST_EXAMPLE_ROUTES = TEST_PATH . 'examples/createAction/Routes.php',
    TEST_ROUTES = self::BUNDLES_CONFIG_PATH . 'Routes.php',
    CACHE_ROUTE_MANAGEMENT = self::CACHE_PHP_INIT_PATH . 'RouteManagement.php',
    CACHE_CLASS_MAP = self::CACHE_PHP_INIT_PATH . 'ClassMap.php',
    CACHE_PROD_CLASS_MAP = self::CACHE_PHP_INIT_PATH . 'ProdClassMap.php',
    CACHE_TASKS_CLASS_MAP = self::CACHE_PHP_INIT_PATH . 'tasksClassMap.php',
    CACHE_TASKS_HELP = self::CACHE_PHP_INIT_PATH . 'tasksHelp.php',
    CACHE_SECURITY_DEV = self::CACHE_PHP_PATH . 'security/dev/' . self::TEST_ROUTE . '.php',
    CACHE_SECURITY_PROD = self::CACHE_PHP_PATH . 'security/prod/' . self::TEST_ROUTE . '.php';

  private static string
    $testRouteCss,
    $testRouteJs,
    $testRouteTemplate;

  // it fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  protected function setUp(): void
  {
    parent::setUp();
    $_SERVER[APP_ENV] = PROD;
    require TEST_PATH . 'config/AllConfigGood.php';
    $cacheKeyGz = sha1('ca' . self::TEST_ROUTE . VERSION . 'che') . '.gz';
    self::$testRouteCss = CACHE_PATH . 'css/' . $cacheKeyGz;
    self::$testRouteJs = CACHE_PATH . 'js/' . $cacheKeyGz;
    self::$testRouteTemplate = CACHE_PATH . 'tpl/' . $cacheKeyGz;
  }

  /**
   * @throws OtraException
   */
  protected function tearDown(): void
  {
    parent::tearDown();
    ob_start();
    require CONSOLE_PATH . 'helpAndTools/generateTaskMetadata/generateTaskMetadataTask.php';
    generateTaskMetadata();
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_GEN_CLASS_MAP,
      [
        self::OTRA_BINARY,
        self::OTRA_TASK_GEN_CLASS_MAP
      ]
    );
    ob_end_clean();
  }

  /**
   * Create dummy files that we will clean with the `clearCache` task. Do nothing of that if the same file names exist
   * already.
   * Copy routes if needed.
   *
   * @param bool $routesNeeded
   *
   * @return void
   */
  private static function createContext(bool $routesNeeded = true) : void
  {
    foreach ([
      self::TEST_CACHE,
      ...self::BOOTSTRAPS,
      self::$testRouteCss,
      self::$testRouteJs,
      self::$testRouteTemplate,
      self::CACHE_ROUTE_MANAGEMENT,
      self::CACHE_CLASS_MAP,
      self::CACHE_PROD_CLASS_MAP,
      self::CACHE_TASKS_CLASS_MAP,
      self::CACHE_TASKS_HELP,
      self::CACHE_SECURITY_DEV,
      self::CACHE_SECURITY_PROD
    ] as $fileToCreate)
    {
      if (!file_exists($fileToCreate))
        touch($fileToCreate);
    }

    if ($routesNeeded)
    {
      if (!file_exists(self::BUNDLES_CONFIG_PATH))
        mkdir(self::BUNDLES_CONFIG_PATH, 0777, true);

      copy(self::TEST_EXAMPLE_ROUTES, self::TEST_ROUTES);
    }
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testNoParameters() : void
  {
    // context
    self::createContext();

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CLEAR_CACHE,
      [
        self::OTRA_BINARY,
        self::OTRA_TASK_CLEAR_CACHE
      ]
    );

    // testing
    self::assertFileDoesNotExist(self::TEST_CACHE);

    // cleaning
    unlink(self::TEST_ROUTES);
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testWrongMask() : void
  {
    // testing
    self::expectException(OtraException::class);
    self::expectExceptionMessage('');
    self::expectOutputString(
      CLI_ERROR . 'Wrong mask value of ' . CLI_INFO_HIGHLIGHT . self::MASK_WRONG . CLI_ERROR . '! It must be between ' .
      CLI_INFO_HIGHLIGHT . self::MASK_INTERNAL_CACHE . CLI_ERROR . ' and ' . CLI_INFO_HIGHLIGHT . self::MASK_ALL .
      CLI_ERROR . '.' . END_COLOR . PHP_EOL
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CLEAR_CACHE,
      [
        self::OTRA_BINARY,
        self::OTRA_TASK_CLEAR_CACHE,
        self::MASK_WRONG
      ]
    );
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testInternalCache() : void
  {
    // context
    self::createContext(false);

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CLEAR_CACHE,
      [
        self::OTRA_BINARY,
        self::OTRA_TASK_CLEAR_CACHE,
        self::MASK_INTERNAL_CACHE
      ]
    );

    // testing
    self::assertFileDoesNotExist(self::TEST_CACHE);

    foreach(self::BOOTSTRAPS as $bootstrap)
    {
      self::assertFileExists($bootstrap);
    }

    self::assertFileExists(self::$testRouteCss);

    self::assertFileExists(self::$testRouteJs);

    self::assertFileExists(self::$testRouteTemplate);

    self::assertFileExists(self::CACHE_ROUTE_MANAGEMENT);

    self::assertFileExists(self::CACHE_CLASS_MAP);
    self::assertFileExists(self::CACHE_PROD_CLASS_MAP);

    self::assertFileExists(self::CACHE_TASKS_CLASS_MAP);
    self::assertFileExists(self::CACHE_TASKS_HELP);

    self::assertFileExists(self::CACHE_SECURITY_DEV);
    self::assertFileExists(self::CACHE_SECURITY_PROD);
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testPhpBootstraps() : void
  {
    // context
    self::createContext();

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CLEAR_CACHE,
      [
        self::OTRA_BINARY,
        self::OTRA_TASK_CLEAR_CACHE,
        self::MASK_BOOTSTRAPS
      ]
    );

    // testing
    self::assertFileExists(self::TEST_CACHE);

    foreach(self::BOOTSTRAPS as $bootstrap)
    {
      self::assertFileDoesNotExist($bootstrap);
    }

    self::assertFileExists(self::$testRouteCss);

    self::assertFileExists(self::$testRouteJs);

    self::assertFileExists(self::$testRouteTemplate);

    self::assertFileExists(self::CACHE_ROUTE_MANAGEMENT);

    self::assertFileExists(self::CACHE_CLASS_MAP);
    self::assertFileExists(self::CACHE_PROD_CLASS_MAP);

    self::assertFileExists(self::CACHE_TASKS_CLASS_MAP);
    self::assertFileExists(self::CACHE_TASKS_HELP);

    self::assertFileExists(self::CACHE_SECURITY_DEV);
    self::assertFileExists(self::CACHE_SECURITY_PROD);
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testCSS() : void
  {
    // context
    self::createContext();

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CLEAR_CACHE,
      [
        self::OTRA_BINARY,
        self::OTRA_TASK_CLEAR_CACHE,
        self::MASK_CSS
      ]
    );

    // testing
    self::assertFileExists(self::TEST_CACHE);

    foreach(self::BOOTSTRAPS as $bootstrap)
    {
      self::assertFileExists($bootstrap);
    }

    self::assertFileDoesNotExist(self::$testRouteCss);

    self::assertFileExists(self::$testRouteJs);

    self::assertFileExists(self::$testRouteTemplate);

    self::assertFileExists(self::CACHE_ROUTE_MANAGEMENT);

    self::assertFileExists(self::CACHE_CLASS_MAP);
    self::assertFileExists(self::CACHE_PROD_CLASS_MAP);

    self::assertFileExists(self::CACHE_TASKS_CLASS_MAP);
    self::assertFileExists(self::CACHE_TASKS_HELP);

    self::assertFileExists(self::CACHE_SECURITY_DEV);
    self::assertFileExists(self::CACHE_SECURITY_PROD);
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testJS() : void
  {
    // context
    self::createContext();

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CLEAR_CACHE,
      [
        self::OTRA_BINARY,
        self::OTRA_TASK_CLEAR_CACHE,
        self::MASK_JS
      ]
    );

    // testing
    self::assertFileExists(self::TEST_CACHE);

    foreach(self::BOOTSTRAPS as $bootstrap)
    {
      self::assertFileExists($bootstrap);
    }

    self::assertFileExists(self::$testRouteCss);

    self::assertFileDoesNotExist(self::$testRouteJs);

    self::assertFileExists(self::$testRouteTemplate);

    self::assertFileExists(self::CACHE_ROUTE_MANAGEMENT);

    self::assertFileExists(self::CACHE_CLASS_MAP);
    self::assertFileExists(self::CACHE_PROD_CLASS_MAP);

    self::assertFileExists(self::CACHE_TASKS_CLASS_MAP);
    self::assertFileExists(self::CACHE_TASKS_HELP);

    self::assertFileExists(self::CACHE_SECURITY_DEV);
    self::assertFileExists(self::CACHE_SECURITY_PROD);
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testTemplates() : void
  {
    // context
    self::createContext();

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CLEAR_CACHE,
      [
        self::OTRA_BINARY,
        self::OTRA_TASK_CLEAR_CACHE,
        self::MASK_TEMPLATES
      ]
    );

    // testing
    self::assertFileExists(self::TEST_CACHE);

    foreach(self::BOOTSTRAPS as $bootstrap)
    {
      self::assertFileExists($bootstrap);
    }

    self::assertFileExists(self::$testRouteCss);

    self::assertFileExists(self::$testRouteJs);

    self::assertFileDoesNotExist(self::$testRouteTemplate);

    self::assertFileExists(self::CACHE_ROUTE_MANAGEMENT);

    self::assertFileExists(self::CACHE_CLASS_MAP);
    self::assertFileExists(self::CACHE_PROD_CLASS_MAP);

    self::assertFileExists(self::CACHE_TASKS_CLASS_MAP);
    self::assertFileExists(self::CACHE_TASKS_HELP);

    self::assertFileExists(self::CACHE_SECURITY_DEV);
    self::assertFileExists(self::CACHE_SECURITY_PROD);
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testRouteManagement() : void
  {
    // context
    self::createContext();

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CLEAR_CACHE,
      [
        self::OTRA_BINARY,
        self::OTRA_TASK_CLEAR_CACHE,
        self::MASK_ROUTE_MANAGEMENT
      ]
    );

    // testing
    self::assertFileExists(self::TEST_CACHE);

    foreach(self::BOOTSTRAPS as $bootstrap)
    {
      self::assertFileExists($bootstrap);
    }

    self::assertFileExists(self::$testRouteCss);

    self::assertFileExists(self::$testRouteJs);

    self::assertFileExists(self::$testRouteTemplate);

    self::assertFileDoesNotExist(self::CACHE_ROUTE_MANAGEMENT);

    self::assertFileExists(self::CACHE_CLASS_MAP);
    self::assertFileExists(self::CACHE_PROD_CLASS_MAP);

    self::assertFileExists(self::CACHE_TASKS_CLASS_MAP);
    self::assertFileExists(self::CACHE_TASKS_HELP);

    self::assertFileExists(self::CACHE_SECURITY_DEV);
    self::assertFileExists(self::CACHE_SECURITY_PROD);
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testClassMapping() : void
  {
    // context
    self::createContext();

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CLEAR_CACHE,
      [
        self::OTRA_BINARY,
        self::OTRA_TASK_CLEAR_CACHE,
        self::MASK_CLASS_MAPPING
      ]
    );

    // testing
    self::assertFileExists(self::TEST_CACHE);

    foreach(self::BOOTSTRAPS as $bootstrap)
    {
      self::assertFileExists($bootstrap);
    }

    self::assertFileExists(self::$testRouteCss);

    self::assertFileExists(self::$testRouteJs);

    self::assertFileExists(self::$testRouteTemplate);

    self::assertFileExists(self::CACHE_ROUTE_MANAGEMENT);

    self::assertFileDoesNotExist(self::CACHE_CLASS_MAP);
    self::assertFileDoesNotExist(self::CACHE_PROD_CLASS_MAP);

    self::assertFileExists(self::CACHE_TASKS_CLASS_MAP);
    self::assertFileExists(self::CACHE_TASKS_HELP);

    self::assertFileExists(self::CACHE_SECURITY_DEV);
    self::assertFileExists(self::CACHE_SECURITY_PROD);
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testConsoleTasksMetadata() : void
  {
    // context
    self::createContext();

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CLEAR_CACHE,
      [
        self::OTRA_BINARY,
        self::OTRA_TASK_CLEAR_CACHE,
        self::MASK_CONSOLE_TASKS_METADATA
      ]
    );

    // testing
    self::assertFileExists(self::TEST_CACHE);

    foreach(self::BOOTSTRAPS as $bootstrap)
    {
      self::assertFileExists($bootstrap);
    }

    self::assertFileExists(self::$testRouteCss);

    self::assertFileExists(self::$testRouteJs);

    self::assertFileExists(self::$testRouteTemplate);

    self::assertFileExists(self::CACHE_ROUTE_MANAGEMENT);

    self::assertFileExists(self::CACHE_CLASS_MAP);
    self::assertFileExists(self::CACHE_PROD_CLASS_MAP);

    self::assertFileDoesNotExist(self::CACHE_TASKS_CLASS_MAP);
    self::assertFileDoesNotExist(self::CACHE_TASKS_HELP);

    self::assertFileExists(self::CACHE_SECURITY_DEV);
    self::assertFileExists(self::CACHE_SECURITY_PROD);
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testSecurity() : void
  {
    // context
    self::createContext();

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CLEAR_CACHE,
      [
        self::OTRA_BINARY,
        self::OTRA_TASK_CLEAR_CACHE,
        self::MASK_SECURITY
      ]
    );

    // testing
    self::assertFileExists(self::TEST_CACHE);

    foreach(self::BOOTSTRAPS as $bootstrap)
    {
      self::assertFileExists($bootstrap);
    }

    self::assertFileExists(self::$testRouteCss);

    self::assertFileExists(self::$testRouteJs);

    self::assertFileExists(self::$testRouteTemplate);

    self::assertFileExists(self::CACHE_ROUTE_MANAGEMENT);

    self::assertFileExists(self::CACHE_CLASS_MAP);
    self::assertFileExists(self::CACHE_PROD_CLASS_MAP);

    self::assertFileExists(self::CACHE_TASKS_CLASS_MAP);
    self::assertFileExists(self::CACHE_TASKS_HELP);

    self::assertFileDoesNotExist(self::CACHE_SECURITY_DEV);
    self::assertFileDoesNotExist(self::CACHE_SECURITY_PROD);
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testAll() : void
  {
    // context
    self::createContext();

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CLEAR_CACHE,
      [
        self::OTRA_BINARY,
        self::OTRA_TASK_CLEAR_CACHE,
        self::MASK_ALL
      ]
    );

    // testing
    self::assertFileDoesNotExist(self::TEST_CACHE);

    foreach(self::BOOTSTRAPS as $bootstrap)
    {
      self::assertFileDoesNotExist($bootstrap);
    }

    self::assertFileDoesNotExist(self::$testRouteCss);

    self::assertFileDoesNotExist(self::$testRouteJs);

    self::assertFileDoesNotExist(self::$testRouteTemplate);

    self::assertFileDoesNotExist(self::CACHE_ROUTE_MANAGEMENT);

    self::assertFileDoesNotExist(self::CACHE_CLASS_MAP);
    self::assertFileDoesNotExist(self::CACHE_PROD_CLASS_MAP);

    self::assertFileDoesNotExist(self::CACHE_TASKS_CLASS_MAP);
    self::assertFileDoesNotExist(self::CACHE_TASKS_HELP);

    self::assertFileDoesNotExist(self::CACHE_SECURITY_DEV);
    self::assertFileDoesNotExist(self::CACHE_SECURITY_PROD);
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testCSSAndRouteManagement() : void
  {
    // context
    self::createContext();

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CLEAR_CACHE,
      [
        self::OTRA_BINARY,
        self::OTRA_TASK_CLEAR_CACHE,
        self::MASK_CSS | self::MASK_ROUTE_MANAGEMENT
      ]
    );

    // testing
    self::assertFileExists(self::TEST_CACHE);

    foreach(self::BOOTSTRAPS as $bootstrap)
    {
      self::assertFileExists($bootstrap);
    }

    self::assertFileDoesNotExist(self::$testRouteCss);

    self::assertFileExists(self::$testRouteJs);

    self::assertFileExists(self::$testRouteTemplate);

    self::assertFileDoesNotExist(self::CACHE_ROUTE_MANAGEMENT);

    self::assertFileExists(self::CACHE_CLASS_MAP);
    self::assertFileExists(self::CACHE_PROD_CLASS_MAP);

    self::assertFileExists(self::CACHE_TASKS_CLASS_MAP);
    self::assertFileExists(self::CACHE_TASKS_HELP);

    self::assertFileExists(self::CACHE_SECURITY_DEV);
    self::assertFileExists(self::CACHE_SECURITY_PROD);
  }
}
