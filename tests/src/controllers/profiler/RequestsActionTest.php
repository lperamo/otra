<?php
declare(strict_types=1);

namespace src\controllers\profiler;

use otra\console\TasksManager;
use otra\controllers\profiler\RequestsAction;
use otra\OtraException;
use phpunit\framework\TestCase;
use function otra\console\convertLongArrayToShort;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\
{APP_ENV, BASE_PATH, BUNDLES_PATH, CACHE_PATH, CORE_PATH, DEV, OTRA_PROJECT, TEST_PATH};
use function otra\tools\delTree;
use const otra\config\VERSION;

/**
 * @runTestsInSeparateProcesses
 */
class RequestsActionTest extends TestCase
{
  private const
    OTRA_TASK_CREATE_HELLO_WORLD = 'createHelloWorld',
    OTRA_PHP_BINARY = 'otra.php',
    HELLO_WORLD_BUNDLE_PATH = BUNDLES_PATH . 'HelloWorld',
    ACTION = 'requests',
    FULL_ACTION_NAME = self::ACTION . 'Action';

  protected $preserveGlobalState = FALSE;

  /**
   * @throws OtraException
   */
  protected function setUp(): void
  {
    parent::setUp();
    $_SERVER[APP_ENV] = DEV;
    ob_start();
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CREATE_HELLO_WORLD,
      [self::OTRA_PHP_BINARY, self::OTRA_TASK_CREATE_HELLO_WORLD]
    );
    ob_end_clean();
  }

  /** Cleaning all the files and folders that have been created */
  protected function tearDown(): void
  {
    parent::tearDown();

    // cleaning
    if (!OTRA_PROJECT)
    {
      require CORE_PATH . 'tools/deleteTree.php';

      /** @var callable $delTree */
      delTree(self::HELLO_WORLD_BUNDLE_PATH);
      delTree(BUNDLES_PATH . 'config/');
      rmdir(BASE_PATH . 'bundles');
    }
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function test() : void
  {
    // context
    require TEST_PATH . 'config/AllConfigGood.php';
    require CORE_PATH . 'templating/blocks.php';
    $_GET['route'] = 'HelloWorld';
    $_GET['session_id'] = session_id();
    $_SERVER['HTTP_HOST'] = 'https://dev.otra-framework.tech';
    file_put_contents(BASE_PATH . 'logs/' . DEV . '/trace.txt', '');
    $responseHeaders = [];
    $headers = headers_list();

    foreach ($headers as $header)
    {
      $header = explode(':', $header);
      $responseHeaders[array_shift($header)] = trim(implode(':', $header));
    }

    // session id equals '' so no need to include it in the file name
    file_put_contents(
      CACHE_PATH . 'php/sessions/' . sha1('ca' . VERSION . 'che') . '.php',
      '<?php declare(strict_types=1);namespace otra\cache\php\sessions;return ' .
      convertLongArrayToShort([
        'GET' => $_GET,
        'POST' => $_POST,
        'COOKIE' => $_COOKIE,
        'SESSION' => [],
        'requestHeaders' => [],
        'responseHeaders' => $responseHeaders,
      ]) . ';' . PHP_EOL
    );

    // launching
    ob_start();
    $requestsAction = new RequestsAction([
      'pattern' => '/profiler/' . self::ACTION,
      'bundle' => '',
      'module' => 'otra',
      'controller' => 'profiler',
      'action' => self::FULL_ACTION_NAME,
      'route' => 'otra_' . self::ACTION,
      'js' => false,
      'css' => false
    ]);
    $output = ob_get_clean();

    // testing
    self::assertInstanceOf(RequestsAction::class, $requestsAction);
  }
}
