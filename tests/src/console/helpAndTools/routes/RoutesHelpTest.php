<?php
declare(strict_types=1);

namespace src\console\helpAndTools\routes;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\cache\php\{CONSOLE_PATH, TEST_PATH};
use const otra\console\{CLI_BASE, CLI_GRAY, CLI_INFO, END_COLOR};
use const otra\bin\TASK_CLASS_MAP_PATH;
use function otra\tests\tools\taskParameter;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class RoutesHelpTest extends TestCase
{
  private const string
    OTRA_CONSOLE_FILENAME = 'otra.php',
    TASK_ROUTES = 'routes',
    OTRA_TASK_HELP = 'help';

  public static function setUpBeforeClass() : void
  {
    parent::setUpBeforeClass();
    // To avoid "Constant otra\console\ADD_BOLD already defined" in this test file
    require_once CONSOLE_PATH . 'colors.php';
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testRoutesHelp(): void
  {
    // context
    require TEST_PATH . 'tools.php';

    // testing
    $this->expectOutputString(
      CLI_BASE .
      str_pad(self::TASK_ROUTES, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Shows the routes and their associated kind of resources in the case they have some. (lightGreen whether they exists, red otherwise)' .
      PHP_EOL .
      taskParameter(
        'route',
        'The name of the route that we want information from, if we wish only one route description.',
        TasksManager::OPTIONAL_PARAMETER
      ) . END_COLOR
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      [self::OTRA_CONSOLE_FILENAME, self::OTRA_TASK_HELP, self::TASK_ROUTES]
    );
  }
}
