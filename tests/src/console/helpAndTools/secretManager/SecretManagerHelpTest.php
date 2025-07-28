<?php
declare(strict_types=1);

namespace src\console\helpAndTools\secretManager;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\{CONSOLE_PATH, TEST_PATH};
use const otra\console\{CLI_BASE, CLI_GRAY, CLI_INFO, CLI_INFO_HIGHLIGHT, END_COLOR};
use function otra\tests\tools\taskParameter;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class SecretManagerHelpTest extends TestCase
{
  private const string
    OTRA_CONSOLE_FILENAME = 'otra.php',
    TASK_ROUTES = 'secretManager',
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
  public function test(): void
  {
    // context
    require TEST_PATH . 'tools.php';

    // testing
    $this->expectOutputString(
      CLI_BASE .
      str_pad(self::TASK_ROUTES, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Manages encrypted secrets securely with an interactive menu. ' . CLI_INFO_HIGHLIGHT . 
      'By default, it uses the "dev" environment.' . CLI_INFO . PHP_EOL. 
      taskParameter(
        'environment',
        'Specifies which environment to use (dev, test, preprod, prod). If not provided, it defaults to "dev".',
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
