<?php
declare(strict_types=1);

namespace src\console\deployment\genServerConfig;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\
{APP_ENV, BASE_PATH, DEV, PROD, TEST_PATH};
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR};

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class GenServerConfigTaskTest extends TestCase
{
  private const
    OTRA_TASK_GEN_SERVER_CONFIG = 'genServerConfig',
    TEST_CONF_PATH = BASE_PATH . 'test.conf',
    TEST_CONF_CACHE_PATH = BASE_PATH . 'test_cache.conf',
    EXAMPLES_PATH = TEST_PATH . 'examples/nginx/',
    EXAMPLE_DEV_TEST_CONF_PATH = self::EXAMPLES_PATH . DEV . '/test.conf',
    EXAMPLE_DEV_TEST_CONF_CACHE_PATH = self::EXAMPLES_PATH . DEV . '/test_cache.conf',
    EXAMPLE_PROD_TEST_CONF_PATH = self::EXAMPLES_PATH . PROD . '/test.conf',
    EXAMPLE_PROD_TEST_CONF_CACHE_PATH = self::EXAMPLES_PATH . PROD . '/test_cache.conf',
    SERVER_TECH = 'nginx',
    OTRA_BINARY = 'otra.php',
    OTRA_TASK_CREATE_HELLO_WORLD = 'createHelloWorld';

  protected function tearDown(): void
  {
    parent::tearDown();

    if (file_exists(self::TEST_CONF_PATH))
      unlink(self::TEST_CONF_PATH);

    if (file_exists(self::TEST_CONF_CACHE_PATH))
      unlink(self::TEST_CONF_CACHE_PATH);
  }

  /**
   * @throws OtraException
   */
  private static function createHelloWorld() : void
  {
    ob_start();
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CREATE_HELLO_WORLD,
      ['otra.php', self::OTRA_TASK_CREATE_HELLO_WORLD]
    );
    ob_end_clean();
  }

  public function testNoDeploymentConfiguration(): void
  {
    // context
    $_SERVER[APP_ENV] = PROD;
    require TEST_PATH . 'config/AllConfigNoDeploymentConfiguration.php';

    // testing
    $this->expectException(OtraException::class);
    $this->expectOutputString(CLI_ERROR .
      'There is no deployment configuration so we cannot know which server name to use.' . END_COLOR . PHP_EOL
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_GEN_SERVER_CONFIG,
      [self::OTRA_BINARY, self::OTRA_TASK_GEN_SERVER_CONFIG, self::TEST_CONF_PATH, PROD, self::SERVER_TECH]
    );
  }

  public function testNoDomainNameInDeploymentConfiguration(): void
  {
    // context
    $_SERVER[APP_ENV] = PROD;
    require TEST_PATH . 'config/AllConfigNoDeploymentDomainName.php';

    // testing
    $this->expectException(OtraException::class);
    $this->expectOutputString(CLI_INFO_HIGHLIGHT . 'domainName' . CLI_ERROR .
      ' is not defined in the deployment configuration so we cannot know which domain name to use.' . END_COLOR . PHP_EOL
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_GEN_SERVER_CONFIG,
      [self::OTRA_BINARY, self::OTRA_TASK_GEN_SERVER_CONFIG, self::TEST_CONF_PATH, PROD, self::SERVER_TECH]
    );
  }

  public function testNoFolderKeyInDeploymentConfiguration(): void
  {
    // context
    $_SERVER[APP_ENV] = PROD;
    require TEST_PATH . 'config/AllConfigNoDeploymentFolder.php';

    // testing
    $this->expectException(OtraException::class);
    $this->expectOutputString(CLI_INFO_HIGHLIGHT . 'folder' . CLI_ERROR .
      ' is not defined in the deployment configuration so we cannot know which server name to use.' . END_COLOR . PHP_EOL
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_GEN_SERVER_CONFIG,
      [self::OTRA_BINARY, self::OTRA_TASK_GEN_SERVER_CONFIG, self::TEST_CONF_PATH, PROD, self::SERVER_TECH]
    );
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testNoRoutes() : void
  {
    // context
    $_SERVER[APP_ENV] = PROD;

    // testing
    $this->expectException(OtraException::class);
    $this->expectOutputString(CLI_ERROR . 'No routes are defined in the project!' . END_COLOR . PHP_EOL);

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_GEN_SERVER_CONFIG,
      [self::OTRA_BINARY, self::OTRA_TASK_GEN_SERVER_CONFIG, self::TEST_CONF_PATH, PROD, self::SERVER_TECH]
    );
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testDevNginx() : void
  {
    // context
    $_SERVER[APP_ENV] = PROD;
    self::createHelloWorld();

    // launching
    ob_start();
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_GEN_SERVER_CONFIG,
      [self::OTRA_BINARY, self::OTRA_TASK_GEN_SERVER_CONFIG, self::TEST_CONF_PATH, DEV, self::SERVER_TECH]
    );

    // testing
    self::assertSame(
      CLI_BASE . 'Nginx development server configuration generated in ' . CLI_INFO_HIGHLIGHT . self::TEST_CONF_PATH .
      CLI_BASE . ' and the cache configuration in ' . CLI_INFO_HIGHLIGHT . self::TEST_CONF_CACHE_PATH . CLI_BASE . '.' . PHP_EOL .
      'Do not forget to include the cache file in your main server configuration file!' . END_COLOR . PHP_EOL,
      ob_get_clean()
    );
    self::assertFileExists(self::TEST_CONF_PATH);
    self::assertFileEquals(
      self::EXAMPLE_DEV_TEST_CONF_PATH,
      self::TEST_CONF_PATH,
      'Testing ' . CLI_INFO_HIGHLIGHT . self::TEST_CONF_PATH . CLI_ERROR . ' against ' . CLI_INFO_HIGHLIGHT .
      self::EXAMPLE_DEV_TEST_CONF_PATH
    );
    self::assertFileEquals(
      self::EXAMPLE_DEV_TEST_CONF_CACHE_PATH,
      self::TEST_CONF_CACHE_PATH,
      'Testing ' . CLI_INFO_HIGHLIGHT . self::TEST_CONF_CACHE_PATH . CLI_ERROR . ' against ' .
      CLI_INFO_HIGHLIGHT . self::EXAMPLE_DEV_TEST_CONF_CACHE_PATH
    );
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testProdNginx() : void
  {
    // context
    $_SERVER[APP_ENV] = PROD;
    self::createHelloWorld();

    // launching
    ob_start();
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_GEN_SERVER_CONFIG,
      [self::OTRA_BINARY, self::OTRA_TASK_GEN_SERVER_CONFIG, self::TEST_CONF_PATH, PROD, self::SERVER_TECH]
    );

    // testing
    self::assertSame(
      CLI_BASE . 'Nginx production server configuration generated in ' . CLI_INFO_HIGHLIGHT . self::TEST_CONF_PATH .
      CLI_BASE . ' and the cache configuration in ' . CLI_INFO_HIGHLIGHT . self::TEST_CONF_CACHE_PATH . CLI_BASE . '.' . PHP_EOL .
      'Do not forget to include the cache file in your main server configuration file!' . END_COLOR . PHP_EOL,
      ob_get_clean()
    );
    self::assertFileExists(self::TEST_CONF_PATH);
    self::assertFileEquals(
      self::EXAMPLE_PROD_TEST_CONF_PATH,
      self::TEST_CONF_PATH,
      'Testing ' . CLI_INFO_HIGHLIGHT . self::TEST_CONF_PATH . CLI_ERROR . ' against ' . CLI_INFO_HIGHLIGHT .
      self::EXAMPLE_PROD_TEST_CONF_PATH
    );
    self::assertFileEquals(
      self::EXAMPLE_PROD_TEST_CONF_CACHE_PATH,
      self::TEST_CONF_CACHE_PATH,
      'Testing ' . CLI_INFO_HIGHLIGHT . self::TEST_CONF_CACHE_PATH . CLI_ERROR . ' against ' . CLI_INFO_HIGHLIGHT .
      self::EXAMPLE_PROD_TEST_CONF_CACHE_PATH
    );
  }
}
