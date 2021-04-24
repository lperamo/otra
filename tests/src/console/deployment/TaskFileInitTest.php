<?php
declare(strict_types=1);

namespace src\console\deployment;

use phpunit\framework\TestCase;
use function otra\console\getPathInformations;

/**
 * @runTestsInSeparateProcesses
 */
class TaskFileInitTest extends TestCase
{
  /**
   * Tests the JS generation without GCC
   *
   * @author Lionel Péramo
   * @throws \otra\OtraException
   */
  public function testGetPathInformations() : void
  {
    // context
    $_SERVER['APP_ENV'] = DEV;
    $argv = [];
    define('TEST_ROUTES_PATH', BASE_PATH . 'bundles/config/');

    if (!file_exists(TEST_ROUTES_PATH))
      mkdir(TEST_ROUTES_PATH, 0777,true);

    require CORE_PATH . 'tools/copyFilesAndFolders.php';
    copyFileAndFolders([TEST_PATH . 'config/Routes.php'], [TEST_ROUTES_PATH . 'Routes.php']);

    // loading files...
    require CORE_PATH . 'console/deployment/taskFileInit.php';

    // defining constants...
    define('TEST_RESOURCES_PATH', TEST_PATH . 'src/bundles/resources/');
    define('TEST_JS_BASE_NAME', 'test');
    define('TEST_TS_RESOURCE_NAME', TEST_RESOURCES_PATH . 'ts/' . TEST_JS_BASE_NAME . '.ts');

    // launching
    $pathInformations = getPathInformations(TEST_TS_RESOURCE_NAME);

    // testing
    self::assertIsArray($pathInformations);
    self::assertEquals(
      [
        TEST_JS_BASE_NAME,
        TEST_RESOURCES_PATH,
        'ts/',
        'ts'
      ],
      $pathInformations
    );
  }
}
