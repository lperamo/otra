<?php
declare(strict_types=1);

namespace src;

use bundles\HelloWorld\frontend\controllers\index\HomeAction;
use otra\console\TasksManager;
use otra\Router;
use phpunit\framework\TestCase;

/**
 * /!\ Beware the bundle HelloWorld will be erased in cleaning phase !
 *
 * @author Lionel Péramo
 * @runTestsInSeparateProcesses
 */
class RouterTest extends TestCase
{
  // fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;
  protected const OTRA_TASK_CREATE_HELLO_WORLD = 'createHelloWorld',
    ROUTE_NAME = 'HelloWorld',
    ROUTE_URL = '/helloworld';

  public static function setUpBeforeClass(): void
  {
    $_SERVER[APP_ENV] = PROD;
    ob_start();
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CREATE_HELLO_WORLD,
      ['otra.php', self::OTRA_TASK_CREATE_HELLO_WORLD]
    );
    ob_end_clean();
  }

  public function testGet_Launch() : void
  {
    // context
    define('TEST_PARAMETERS_ARRAY', ['test' => 'coucou']);

    // launching
    ob_start();
    $route = Router::get(self::ROUTE_NAME, TEST_PARAMETERS_ARRAY);
    $output = ob_get_clean();

    // testing
    // we are using substr to remove the end of line character
    self::assertMatchesRegularExpression(
      '@' .
      substr(file_get_contents(TEST_PATH . 'examples/helloWorld.phtml'), 0, strlen(PHP_EOL)) . '@',
      $output,
      'Testing the output...'
    );
    self::assertEquals(
      new HomeAction(
        [
          'pattern' => self::ROUTE_URL,
          'bundle' => self::ROUTE_NAME,
          'module' => 'frontend',
          'controller' => 'index',
          'action' => 'HomeAction',
          'route' => self::ROUTE_NAME,
          'js' => false,
          'css' => false
        ],
        TEST_PARAMETERS_ARRAY
      ),
      $route,
      'Testing route url...'
    );
  }

  public function testGet_DoNotLaunch() : void
  {
    // launching
    $route = Router::get(self::ROUTE_NAME, ['test' => 'coucou'], false);

    // testing
    self::assertEquals(
      'bundles\HelloWorld\frontend\controllers\index\HomeAction',
      $route,
      'Testing action path...'
    );
  }

  public function testGetByPattern() : void
  {
    // launching
    $route = Router::getByPattern(self::ROUTE_URL);

    // testing
    self::assertEquals(
      self::ROUTE_NAME,
      $route[Router::OTRA_ROUTER_GET_BY_PATTERN_METHOD_ROUTE_NAME],
      'Testing route name...'
    );
    self::assertEquals(
      [],
      $route[Router::OTRA_ROUTER_GET_BY_PATTERN_METHOD_PARAMS],
      'Testing route params...'
    );
  }

  public function testGetByPattern_NonExistentRoute_firstCase() : void
  {
    // launching
    $route = Router::getByPattern('/hellow');

    // testing
    self::assertEquals(
      'otra_404',
      $route[Router::OTRA_ROUTER_GET_BY_PATTERN_METHOD_ROUTE_NAME],
      'Testing route name...'
    );
    self::assertEquals(
      [],
      $route[Router::OTRA_ROUTER_GET_BY_PATTERN_METHOD_PARAMS],
      'Testing route params...'
    );
  }

  public function testGetByPattern_NonExistentRoute_secondCase() : void
  {
    // launching
    $route = Router::getByPattern(self::ROUTE_URL . 'test');

    // testing
    self::assertEquals(
      'otra_404',
      $route[Router::OTRA_ROUTER_GET_BY_PATTERN_METHOD_ROUTE_NAME],
      'Testing route name...'
    );
    self::assertEquals(
      [],
      $route[Router::OTRA_ROUTER_GET_BY_PATTERN_METHOD_PARAMS],
      'Testing route params...'
    );
  }


  public function testGetRouteUrl() : void
  {
    // launching
    $route = Router::getRouteUrl(self::ROUTE_NAME);

    // testing
    self::assertEquals(
      self::ROUTE_URL,
      $route,
      'Testing route url...'
    );
  }
}
