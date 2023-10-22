<?php
declare(strict_types=1);

namespace src\console\helpAndTools\checkConfiguration;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\{BUNDLES_PATH, TEST_PATH};
use const otra\console\
{
  ADD_BOLD,
  CLI_BASE,
  CLI_ERROR,
  CLI_INFO_HIGHLIGHT,
  CLI_SUCCESS,
  CLI_WARNING,
  END_COLOR,
  REMOVE_BOLD_INTENSITY
};

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class CheckConfigurationTaskTest extends TestCase
{
  private const
    OTRA_TASK_CHECK_CONFIGURATION = 'checkConfiguration',
    EXAMPLES_PATH = TEST_PATH . 'examples/tools/routesCheck/',
    ROUTE_ALLOWED_PARAMETERS = [
      'bootstrap',
      'chunks',
      'get',
      'post',
    self::LABEL_RESOURCES,
      'session'
    ],
    ROUTE_RESOURCES_ALLOWED_PARAMETERS = [
      'app_css',
      'app_js',
      'bundle_css',
      'bundle_js',
      'core_css',
      'core_js',
      'module_css',
      'module_js',
      'print_css',
      'template'
    ],
    HELLO_WORLD_FOLDER = BUNDLES_PATH . 'HelloWorld/',
    HELLO_WORLD_CONFIG_FOLDER = self::HELLO_WORLD_FOLDER . 'config/',
    CHECK_RESOURCE_TYPE = 0,
    CHECK_RESOURCE_VALUE = 1,
    CHECK_RESOURCE_BACKUP_VALUE = 2,
    CHECK_RESOURCE_TYPE_FILE = 0,
    CHECK_RESOURCE_TYPE_FOLDER = 1,
    LABEL_CHECKING_ROUTES_CONFIG = 'Checking routes configuration...' . PHP_EOL,
    LABEL_YOUR_ROUTE_PARAMETER = 'Your route parameter ',
    LABEL_RESOURCES = 'resources',
    LABEL_MUST_BE_AN_ARRAY = ' must be an array.',
    LABEL_YOUR_ROUTE_CONFIGURATION = 'Your route configuration ',
    LABEL_YOU_HAVE = 'You have ',
    LABEL_WARNING_ERROR_IN_YOUR_ROUTES_CONFIGURATION = ' warning/error in your routes configuration.',
    ROUTES_PHP = 'Routes.php',
    OTRA_PHP_BINARY = 'otra.php';

  private static string $labelAnalyzingFiles = '';

  /** @var array<int,array{0:string,1:string} Folder or file paths that will have to be clean */
  public static array $resourcesToClean = [];

  /**
   * If the folders/files do not exist create them and return them in an array.
   *
   * @param array<int,array{0:string,1:string} $resourcePaths Folder or file paths to tests with file_exists.
   */
  private static function checkAndCreate(array $resourcePaths): void
  {
    foreach($resourcePaths as $resourcePath)
    {
      $pathExist = file_exists($resourcePath[self::CHECK_RESOURCE_VALUE]);

      if (!$pathExist)
      {
        array_unshift(
          self::$resourcesToClean,
          [
            $resourcePath[self::CHECK_RESOURCE_TYPE],
            $resourcePath[self::CHECK_RESOURCE_VALUE]
          ]
        );

        if ($resourcePath[self::CHECK_RESOURCE_TYPE] === self::CHECK_RESOURCE_TYPE_FILE)
          copy(
            $resourcePath[self::CHECK_RESOURCE_BACKUP_VALUE],
            $resourcePath[self::CHECK_RESOURCE_VALUE]
          );
        else
          mkdir($resourcePath[self::CHECK_RESOURCE_VALUE]);
      }
    }
  }

  protected function setUp(): void
  {
    parent::setUp();
    self::$labelAnalyzingFiles = 'Analyzing the file ' . ADD_BOLD . CLI_BASE . '[BASE_PATH]' .
      REMOVE_BOLD_INTENSITY . CLI_INFO_HIGHLIGHT . 'bundles/HelloWorld/config/Routes.php' . CLI_BASE . '...' .
      END_COLOR . PHP_EOL;
  }

  /** Cleaning all the files and folders that have been created */
  protected function tearDown(): void
  {
    parent::tearDown();

    foreach(self::$resourcesToClean as $resourceToClean)
    {
      if ($resourceToClean[self::CHECK_RESOURCE_TYPE] === self::CHECK_RESOURCE_TYPE_FOLDER)
        rmdir($resourceToClean[self::CHECK_RESOURCE_VALUE]);
      else
        unlink($resourceToClean[self::CHECK_RESOURCE_VALUE]);
    }

    // re-initializes the $resourcesToClean variable
    self::$resourcesToClean = [];
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testCheckConfiguration() : void
  {
    // context
    self::checkAndCreate(
      [
        [self::CHECK_RESOURCE_TYPE_FOLDER, BUNDLES_PATH],
        [self::CHECK_RESOURCE_TYPE_FOLDER, self::HELLO_WORLD_FOLDER],
        [self::CHECK_RESOURCE_TYPE_FOLDER, self::HELLO_WORLD_CONFIG_FOLDER],
        [
          self::CHECK_RESOURCE_TYPE_FILE,
          self::HELLO_WORLD_CONFIG_FOLDER . self::ROUTES_PHP,
          self::EXAMPLES_PATH . 'goodConfiguration.php'
        ]
      ]
    );

    // testing
    $this->expectOutputString(
      self::LABEL_CHECKING_ROUTES_CONFIG .
      self::$labelAnalyzingFiles .
      CLI_BASE . 'You do not have any problems in your routes configuration' . CLI_SUCCESS . ' ✔' . END_COLOR . PHP_EOL
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CHECK_CONFIGURATION,
      [self::OTRA_PHP_BINARY, self::OTRA_TASK_CHECK_CONFIGURATION]
    );
  }

  public function testNoBundles() : void
  {
    // testing
    $this->expectOutputString(CLI_ERROR . 'There are no bundles to use!' . END_COLOR . PHP_EOL);
    $this->expectException(OtraException::class);
    $this->expectExceptionMessage('');

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CHECK_CONFIGURATION,
      [self::OTRA_PHP_BINARY, self::OTRA_TASK_CHECK_CONFIGURATION]
    );
  }

  /**
   * @throws OtraException
   */
  public function testNoStrictTypeDeclaration() : void
  {
    // context
    self::checkAndCreate(
      [
        [self::CHECK_RESOURCE_TYPE_FOLDER, BUNDLES_PATH],
        [self::CHECK_RESOURCE_TYPE_FOLDER, self::HELLO_WORLD_FOLDER],
        [self::CHECK_RESOURCE_TYPE_FOLDER, self::HELLO_WORLD_CONFIG_FOLDER],
        [
          self::CHECK_RESOURCE_TYPE_FILE,
          self::HELLO_WORLD_CONFIG_FOLDER . self::ROUTES_PHP,
          self::EXAMPLES_PATH . 'noStrictTypeDeclaration.php'
        ]
      ]
    );

    // testing
    $this->expectOutputString(
      self::LABEL_CHECKING_ROUTES_CONFIG .
      self::$labelAnalyzingFiles .
      CLI_WARNING . 'You must put a ' . CLI_INFO_HIGHLIGHT . 'declare(strict_types=1);' . CLI_WARNING . ' declaration.' .
      END_COLOR . PHP_EOL .
      CLI_WARNING . self::LABEL_YOU_HAVE . CLI_INFO_HIGHLIGHT . '1' . CLI_WARNING .
      self::LABEL_WARNING_ERROR_IN_YOUR_ROUTES_CONFIGURATION
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CHECK_CONFIGURATION,
      [self::OTRA_PHP_BINARY, self::OTRA_TASK_CHECK_CONFIGURATION]
    );
  }

  /**
   * @throws OtraException
   */
  public function testNoRoutes() : void
  {
    // context
    self::checkAndCreate(
      [
        [self::CHECK_RESOURCE_TYPE_FOLDER, BUNDLES_PATH],
        [self::CHECK_RESOURCE_TYPE_FOLDER, self::HELLO_WORLD_FOLDER],
        [self::CHECK_RESOURCE_TYPE_FOLDER, self::HELLO_WORLD_CONFIG_FOLDER],
        [
          self::CHECK_RESOURCE_TYPE_FILE,
          self::HELLO_WORLD_CONFIG_FOLDER . self::ROUTES_PHP,
          self::EXAMPLES_PATH . 'noRoutes.php'
        ]
      ]
    );

    // testing
    $this->expectOutputString(
      self::LABEL_CHECKING_ROUTES_CONFIG .
      self::$labelAnalyzingFiles .
      CLI_WARNING . 'Your routes array is empty!' . END_COLOR . PHP_EOL .
      CLI_WARNING . self::LABEL_YOU_HAVE . CLI_INFO_HIGHLIGHT . '1' . CLI_WARNING . self::LABEL_WARNING_ERROR_IN_YOUR_ROUTES_CONFIGURATION
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CHECK_CONFIGURATION,
      [self::OTRA_PHP_BINARY, self::OTRA_TASK_CHECK_CONFIGURATION]
    );
  }

  /**
   * @throws OtraException
   */
  public function testNotAString() : void
  {
    // context
    self::checkAndCreate(
      [
        [self::CHECK_RESOURCE_TYPE_FOLDER, BUNDLES_PATH],
        [self::CHECK_RESOURCE_TYPE_FOLDER, self::HELLO_WORLD_FOLDER],
        [self::CHECK_RESOURCE_TYPE_FOLDER, self::HELLO_WORLD_CONFIG_FOLDER],
        [
          self::CHECK_RESOURCE_TYPE_FILE,
          self::HELLO_WORLD_CONFIG_FOLDER . self::ROUTES_PHP,
          self::EXAMPLES_PATH . 'notAString.php'
        ]
      ]
    );

    // testing
    $this->expectOutputString(
      self::LABEL_CHECKING_ROUTES_CONFIG .
      self::$labelAnalyzingFiles .
      CLI_WARNING . 'Your route ' . CLI_INFO_HIGHLIGHT . '3' . CLI_WARNING . ' is not a string!' . END_COLOR . PHP_EOL .
      CLI_WARNING . self::LABEL_YOU_HAVE . CLI_INFO_HIGHLIGHT . '1' . CLI_WARNING . self::LABEL_WARNING_ERROR_IN_YOUR_ROUTES_CONFIGURATION
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CHECK_CONFIGURATION,
      [self::OTRA_PHP_BINARY, self::OTRA_TASK_CHECK_CONFIGURATION]
    );
  }

  /**
   * @throws OtraException
   */
  public function testIsEmpty() : void
  {
    // context
    self::checkAndCreate(
      [
        [self::CHECK_RESOURCE_TYPE_FOLDER, BUNDLES_PATH],
        [self::CHECK_RESOURCE_TYPE_FOLDER, self::HELLO_WORLD_FOLDER],
        [self::CHECK_RESOURCE_TYPE_FOLDER, self::HELLO_WORLD_CONFIG_FOLDER],
        [
          self::CHECK_RESOURCE_TYPE_FILE,
          self::HELLO_WORLD_CONFIG_FOLDER . self::ROUTES_PHP,
          self::EXAMPLES_PATH . 'isEmpty.php'
        ]
      ]
    );

    // testing
    $this->expectOutputString(
      self::LABEL_CHECKING_ROUTES_CONFIG .
      self::$labelAnalyzingFiles .
      CLI_WARNING . 'Your route ' . CLI_INFO_HIGHLIGHT . 'HelloWorld' . CLI_WARNING . ' is empty!' . END_COLOR . PHP_EOL .
      CLI_WARNING . self::LABEL_YOU_HAVE . CLI_INFO_HIGHLIGHT . '1' . CLI_WARNING . self::LABEL_WARNING_ERROR_IN_YOUR_ROUTES_CONFIGURATION
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CHECK_CONFIGURATION,
      [self::OTRA_PHP_BINARY, self::OTRA_TASK_CHECK_CONFIGURATION]
    );
  }

  /**
   * @throws OtraException
   */
  public function testConfigurationIsNotAString() : void
  {
    // context
    self::checkAndCreate(
      [
        [self::CHECK_RESOURCE_TYPE_FOLDER, BUNDLES_PATH],
        [self::CHECK_RESOURCE_TYPE_FOLDER, self::HELLO_WORLD_FOLDER],
        [self::CHECK_RESOURCE_TYPE_FOLDER, self::HELLO_WORLD_CONFIG_FOLDER],
        [
          self::CHECK_RESOURCE_TYPE_FILE,
          self::HELLO_WORLD_CONFIG_FOLDER . self::ROUTES_PHP,
          self::EXAMPLES_PATH . 'configurationIsNotAString.php'
        ]
      ]
    );

    // testing
    $this->expectOutputString(
      self::LABEL_CHECKING_ROUTES_CONFIG .
      self::$labelAnalyzingFiles .
      CLI_WARNING . self::LABEL_YOUR_ROUTE_CONFIGURATION . CLI_INFO_HIGHLIGHT . '3' . CLI_WARNING . ' is not a string!' .
      END_COLOR . PHP_EOL . CLI_WARNING . self::LABEL_YOUR_ROUTE_PARAMETER . CLI_INFO_HIGHLIGHT . '3' . CLI_WARNING .
      ' does not exist! It can be : ' . implode(',', self::ROUTE_ALLOWED_PARAMETERS) . '.' . END_COLOR .
      PHP_EOL .
      CLI_WARNING . self::LABEL_YOU_HAVE . CLI_INFO_HIGHLIGHT . '2' . CLI_WARNING . ' warnings/errors in your routes configuration.'
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CHECK_CONFIGURATION,
      [self::OTRA_PHP_BINARY, self::OTRA_TASK_CHECK_CONFIGURATION]
    );
  }

  /**
   * @throws OtraException
   */
  public function testWrongParameter() : void
  {
    // context
    self::checkAndCreate(
      [
        [self::CHECK_RESOURCE_TYPE_FOLDER, BUNDLES_PATH],
        [self::CHECK_RESOURCE_TYPE_FOLDER, self::HELLO_WORLD_FOLDER],
        [self::CHECK_RESOURCE_TYPE_FOLDER, self::HELLO_WORLD_CONFIG_FOLDER],
        [
          self::CHECK_RESOURCE_TYPE_FILE,
          self::HELLO_WORLD_CONFIG_FOLDER . self::ROUTES_PHP,
          self::EXAMPLES_PATH . 'wrongParameter.php'
        ]
      ]
    );

    // testing
    $this->expectOutputString(
      self::LABEL_CHECKING_ROUTES_CONFIG .
      self::$labelAnalyzingFiles .
      CLI_WARNING . self::LABEL_YOUR_ROUTE_PARAMETER . CLI_INFO_HIGHLIGHT . 'coucou' . CLI_WARNING .
      ' does not exist! It can be : ' . implode(',', self::ROUTE_ALLOWED_PARAMETERS) . '.' . END_COLOR .
      PHP_EOL .
      CLI_WARNING . self::LABEL_YOU_HAVE . CLI_INFO_HIGHLIGHT . '1' . CLI_WARNING . self::LABEL_WARNING_ERROR_IN_YOUR_ROUTES_CONFIGURATION
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CHECK_CONFIGURATION,
      [self::OTRA_PHP_BINARY, self::OTRA_TASK_CHECK_CONFIGURATION]
    );
  }

  /**
   * @throws OtraException
   */
  public function testConfigurationIsEmpty() : void
  {
    // context
    self::checkAndCreate(
      [
        [self::CHECK_RESOURCE_TYPE_FOLDER, BUNDLES_PATH],
        [self::CHECK_RESOURCE_TYPE_FOLDER, self::HELLO_WORLD_FOLDER],
        [self::CHECK_RESOURCE_TYPE_FOLDER, self::HELLO_WORLD_CONFIG_FOLDER],
        [
          self::CHECK_RESOURCE_TYPE_FILE,
          self::HELLO_WORLD_CONFIG_FOLDER . self::ROUTES_PHP,
          self::EXAMPLES_PATH . 'configurationIsEmpty.php'
        ]
      ]
    );

    // testing
    $this->expectOutputString(
      self::LABEL_CHECKING_ROUTES_CONFIG .
      self::$labelAnalyzingFiles .
      CLI_WARNING . self::LABEL_YOUR_ROUTE_CONFIGURATION . CLI_INFO_HIGHLIGHT . 'chunks' . CLI_WARNING . ' is empty!' . END_COLOR .
      PHP_EOL .
      CLI_WARNING . self::LABEL_YOU_HAVE . CLI_INFO_HIGHLIGHT . '1' . CLI_WARNING . self::LABEL_WARNING_ERROR_IN_YOUR_ROUTES_CONFIGURATION
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CHECK_CONFIGURATION,
      [self::OTRA_PHP_BINARY, self::OTRA_TASK_CHECK_CONFIGURATION]
    );
  }

  /**
   * @throws OtraException
   */
  public function testConfigurationArrayParameters() : void
  {
    // context
    self::checkAndCreate(
      [
        [self::CHECK_RESOURCE_TYPE_FOLDER, BUNDLES_PATH],
        [self::CHECK_RESOURCE_TYPE_FOLDER, self::HELLO_WORLD_FOLDER],
        [self::CHECK_RESOURCE_TYPE_FOLDER, self::HELLO_WORLD_CONFIG_FOLDER],
        [
          self::CHECK_RESOURCE_TYPE_FILE,
          self::HELLO_WORLD_CONFIG_FOLDER . self::ROUTES_PHP,
          self::EXAMPLES_PATH . 'configurationArrayParameters.php'
        ]
      ]
    );

    // testing
    $this->expectOutputString(
      self::LABEL_CHECKING_ROUTES_CONFIG .
      self::$labelAnalyzingFiles .
      CLI_WARNING . self::LABEL_YOUR_ROUTE_CONFIGURATION . CLI_INFO_HIGHLIGHT . 'chunks' . CLI_WARNING .
      self::LABEL_MUST_BE_AN_ARRAY . END_COLOR . PHP_EOL .
      CLI_WARNING . self::LABEL_YOU_HAVE . CLI_INFO_HIGHLIGHT . '1' . CLI_WARNING . self::LABEL_WARNING_ERROR_IN_YOUR_ROUTES_CONFIGURATION
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CHECK_CONFIGURATION,
      [self::OTRA_PHP_BINARY, self::OTRA_TASK_CHECK_CONFIGURATION]
    );
  }

  /**
   * @throws OtraException
   */
  public function testConfigurationChunksWrongCount() : void
  {
    // context
    self::checkAndCreate(
      [
        [self::CHECK_RESOURCE_TYPE_FOLDER, BUNDLES_PATH],
        [self::CHECK_RESOURCE_TYPE_FOLDER, self::HELLO_WORLD_FOLDER],
        [self::CHECK_RESOURCE_TYPE_FOLDER, self::HELLO_WORLD_CONFIG_FOLDER],
        [
          self::CHECK_RESOURCE_TYPE_FILE,
          self::HELLO_WORLD_CONFIG_FOLDER . self::ROUTES_PHP,
          self::EXAMPLES_PATH . 'chunksWrongCount.php'
        ]
      ]
    );

    // testing
    $this->expectOutputString(
      self::LABEL_CHECKING_ROUTES_CONFIG .
      self::$labelAnalyzingFiles .
      CLI_WARNING . self::LABEL_YOUR_ROUTE_CONFIGURATION . CLI_INFO_HIGHLIGHT . 'chunks' . CLI_WARNING .
      ' must have 5 parameters : url, bundle, module, controller and action. You currently have ' .
      CLI_INFO_HIGHLIGHT . 4 . CLI_WARNING . ' parameters!' . END_COLOR .
      PHP_EOL .
      CLI_WARNING . self::LABEL_YOU_HAVE . CLI_INFO_HIGHLIGHT . '1' . CLI_WARNING . self::LABEL_WARNING_ERROR_IN_YOUR_ROUTES_CONFIGURATION
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CHECK_CONFIGURATION,
      [self::OTRA_PHP_BINARY, self::OTRA_TASK_CHECK_CONFIGURATION]
    );
  }

  /**
   * @throws OtraException
   */
  public function testWrongResourcesParameter() : void
  {
    // context
    self::checkAndCreate(
      [
        [self::CHECK_RESOURCE_TYPE_FOLDER, BUNDLES_PATH],
        [self::CHECK_RESOURCE_TYPE_FOLDER, self::HELLO_WORLD_FOLDER],
        [self::CHECK_RESOURCE_TYPE_FOLDER, self::HELLO_WORLD_CONFIG_FOLDER],
        [
          self::CHECK_RESOURCE_TYPE_FILE,
          self::HELLO_WORLD_CONFIG_FOLDER . self::ROUTES_PHP,
          self::EXAMPLES_PATH . 'wrongResourcesParameter.php'
        ]
      ]
    );

    // testing
    $this->expectOutputString(
      self::LABEL_CHECKING_ROUTES_CONFIG .
      self::$labelAnalyzingFiles .
      CLI_WARNING . self::LABEL_YOUR_ROUTE_PARAMETER . CLI_INFO_HIGHLIGHT . self::LABEL_RESOURCES . CLI_WARNING .
      ' contains a parameter ' . CLI_INFO_HIGHLIGHT . 'test' . CLI_WARNING .
      ' that does not exist! It can be : ' . implode(',', self::ROUTE_RESOURCES_ALLOWED_PARAMETERS) .
      '.' . END_COLOR . PHP_EOL .
      CLI_WARNING . self::LABEL_YOU_HAVE . CLI_INFO_HIGHLIGHT . '1' . CLI_WARNING . self::LABEL_WARNING_ERROR_IN_YOUR_ROUTES_CONFIGURATION
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CHECK_CONFIGURATION,
      [self::OTRA_PHP_BINARY, self::OTRA_TASK_CHECK_CONFIGURATION]
    );
  }

  /**
   * @throws OtraException
   */
  public function testResourcesArrayParameters() : void
  {
    // context
    self::checkAndCreate(
      [
        [self::CHECK_RESOURCE_TYPE_FOLDER, BUNDLES_PATH],
        [self::CHECK_RESOURCE_TYPE_FOLDER, self::HELLO_WORLD_FOLDER],
        [self::CHECK_RESOURCE_TYPE_FOLDER, self::HELLO_WORLD_CONFIG_FOLDER],
        [
          self::CHECK_RESOURCE_TYPE_FILE,
          self::HELLO_WORLD_CONFIG_FOLDER . self::ROUTES_PHP,
          self::EXAMPLES_PATH . 'resourcesArrayParameters.php'
        ]
      ]
    );

    // testing
    $this->expectOutputString(
      self::LABEL_CHECKING_ROUTES_CONFIG .
      self::$labelAnalyzingFiles .
      CLI_WARNING . 'Your route resources parameter ' . CLI_INFO_HIGHLIGHT . 'module_css' . CLI_WARNING .
      self::LABEL_MUST_BE_AN_ARRAY . END_COLOR . PHP_EOL .
      CLI_WARNING . self::LABEL_YOU_HAVE . CLI_INFO_HIGHLIGHT . '1' . CLI_WARNING . self::LABEL_WARNING_ERROR_IN_YOUR_ROUTES_CONFIGURATION
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CHECK_CONFIGURATION,
      [self::OTRA_PHP_BINARY, self::OTRA_TASK_CHECK_CONFIGURATION]
    );
  }
}
