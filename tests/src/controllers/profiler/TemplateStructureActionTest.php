<?php
declare(strict_types=1);

namespace src\controllers\profiler;

use otra\console\TasksManager;
use otra\controllers\profiler\TemplateStructureAction;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\{APP_ENV, BASE_PATH, BUNDLES_PATH, CORE_PATH, DEV, OTRA_PROJECT, TEST_PATH};
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT};
use function otra\tools\delTree;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class TemplateStructureActionTest extends TestCase
{
  private const string
    OTRA_TASK_CREATE_HELLO_WORLD = 'createHelloWorld',
    OTRA_PHP_BINARY = 'otra.php',
    HELLO_WORLD_BUNDLE_PATH = BUNDLES_PATH . 'HelloWorld',
    ACTION = 'templateStructure',
    FULL_ACTION_NAME = self::ACTION . 'Action',
    TEST_TEMPLATE = TEST_PATH . 'examples/profiler/' . self::FULL_ACTION_NAME. '.phtml';

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
   * @medium
   * @author Lionel Péramo
   * @throws OtraException|ReflectionException
   */
  public function test() : void
  {
    // context
    require CORE_PATH . 'templating/blocks.php';
    $_GET['route'] = 'HelloWorld';
    $_SERVER['HTTP_HOST'] = 'https://dev.otra-framework.tech';
    $_SERVER['LOGIN'] = 'https://dev.otra-framework.tech';
    $_SERVER['PASSWORD'] = 'https://dev.otra-framework.tech';
    require TEST_PATH . 'config/AllConfigGood.php';

    // launching
    ob_start();
    $templateStructureAction = new TemplateStructureAction([
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
    ob_start();
    require self::TEST_TEMPLATE;
    self::assertInstanceOf(TemplateStructureAction::class, $templateStructureAction);
    self::assertSame(
      ob_get_clean(),
      preg_replace(
        [
          '@<link rel=stylesheet nonce=[0-9a-z]{64} href=[^.]+.css>@',
          '@<script nonce=\w{64} src=/src/resources/js/profiler.js></script>@'
        ],
        '',
        $output
      ),
      'Testing profiler ' . CLI_INFO_HIGHLIGHT . self::FULL_ACTION_NAME . CLI_ERROR . ' page output with ' .
      CLI_INFO_HIGHLIGHT . self::TEST_TEMPLATE . CLI_ERROR . '...'
    );
  }
}
