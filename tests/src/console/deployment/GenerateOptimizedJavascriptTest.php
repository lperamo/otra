<?php
declare(strict_types=1);

namespace src\console\deployment;

use phpunit\framework\TestCase;
use function otra\tools\files\returnLegiblePath;

/**
 * @runTestsInSeparateProcesses
 */
class GenerateOptimizedJavascriptTest extends TestCase
{
  /**
   * Tests the JS generation without GCC
   *
   * @author Lionel Péramo
   * @throws \otra\OtraException
   */
  public function testGenerateOptimizedJavascript() : void
  {
    // context
    $_SERVER['APP_ENV'] = 'dev';

    // loading files...
    require CORE_PATH . 'tools/cli.php';
    require CORE_PATH . 'tools/files/returnLegiblePath.php';
    require CORE_PATH . 'console/deployment/generateOptimizedJavaScript.php';

    // defining constants...
    define('TEST_RESOURCES_PATH', TEST_PATH . 'src/bundles/resources/');
    define('TEST_JS_BASE_NAME', 'test');
    define('TEST_JS_RESOURCE_FOLDER', TEST_RESOURCES_PATH . 'js/');
    define('TEST_TS_RESOURCE_NAME', TEST_RESOURCES_PATH . 'ts/' . TEST_JS_BASE_NAME . '.ts');
    define('TEST_JS_RESOURCE_NAME', TEST_JS_RESOURCE_FOLDER . TEST_JS_BASE_NAME . '.js');
    define('TEST_JS_MAP_RESOURCE_NAME', TEST_JS_RESOURCE_NAME . '.map');
    define('TEST_TEMPORARY_JS_FILE', TEST_JS_RESOURCE_FOLDER . TEST_JS_BASE_NAME . '_viaTypescript.js');

    // testing
    self::expectOutputString(
      CLI_GREEN . 'TypeScript file ' .
      returnLegiblePath(TEST_TS_RESOURCE_NAME, '', false) . CLI_GREEN .
  ' have generated the temporary files ' . returnLegiblePath(TEST_TEMPORARY_JS_FILE) . CLI_GREEN . ' and ' .
          returnLegiblePath(TEST_TEMPORARY_JS_FILE . '.map', '', false) . CLI_GREEN . '.' .
      END_COLOR . PHP_EOL . PHP_EOL
    );

    // launching
    generateJavaScript(
      1,
      false,
      TEST_JS_RESOURCE_FOLDER,
      TEST_JS_BASE_NAME,
      TEST_TS_RESOURCE_NAME
    );

    // testing
    self::assertFileExists(TEST_JS_RESOURCE_NAME);
    self::assertFileExists(TEST_JS_MAP_RESOURCE_NAME);

    // cleaning
    unlink(TEST_JS_RESOURCE_NAME);
    unlink(TEST_JS_MAP_RESOURCE_NAME);
  }
}
