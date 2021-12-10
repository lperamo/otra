<?php
declare(strict_types=1);

namespace src\console\deployment;

use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\cache\php\{APP_ENV,CORE_PATH,DEV,TEST_PATH};
use const otra\console\{CLI_BASE, SUCCESS};
use function otra\console\deployment\generateJavaScript;
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
   * @throws OtraException
   */
  public function testGenerateOptimizedJavascript() : void
  {
    // context
    $_SERVER[APP_ENV] = DEV;

    // loading files...
    require CORE_PATH . 'tools/cli.php';
    require_once CORE_PATH . 'tools/files/returnLegiblePath.php';
    require CORE_PATH . 'console/deployment/generateOptimizedJavaScript.php';

    // defining constants...
    define(__NAMESPACE__ . '\\TEST_RESOURCES_PATH', TEST_PATH . 'src/bundles/resources/');
    define(__NAMESPACE__ . '\\TEST_JS_BASE_NAME', 'test');
    define(__NAMESPACE__ . '\\TEST_JS_RESOURCE_FOLDER', TEST_RESOURCES_PATH . 'js/');
    define(__NAMESPACE__ . '\\TEST_TS_RESOURCE_NAME', TEST_RESOURCES_PATH . 'devJs/' . TEST_JS_BASE_NAME . '.ts');
    define(__NAMESPACE__ . '\\TEST_JS_RESOURCE_NAME', TEST_JS_RESOURCE_FOLDER . TEST_JS_BASE_NAME . '.js');
    define(__NAMESPACE__ . '\\TEST_JS_MAP_RESOURCE_NAME', TEST_JS_RESOURCE_NAME . '.map');
    define(__NAMESPACE__ . '\\TEST_TEMPORARY_JS_FILE', TEST_JS_RESOURCE_FOLDER . TEST_JS_BASE_NAME . '_viaTypescript.js');

    // testing
    self::expectOutputString(
      CLI_BASE . 'TypeScript file ' .
      returnLegiblePath(TEST_TS_RESOURCE_NAME, '') . CLI_BASE .
  ' have generated the temporary files ' . returnLegiblePath(TEST_TEMPORARY_JS_FILE, '') . CLI_BASE . ' and ' .
          returnLegiblePath(TEST_TEMPORARY_JS_FILE . '.map', '') . SUCCESS . PHP_EOL
    );

    // launching
    generateJavaScript(
      false,
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
