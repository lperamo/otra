<?php
declare(strict_types=1);

namespace src\console\database\sqlCreateFixtures;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\console\
{CLI_BASE, CLI_GRAY, CLI_INFO, CLI_INFO_HIGHLIGHT, END_COLOR, STRING_PAD_FOR_OPTION_FORMATTING};
use const otra\bin\TASK_CLASS_MAP_PATH;


/**
 * @runTestsInSeparateProcesses
 */
class sqlCreateFixturesHelpTest extends TestCase
{

  private const OTRA_TASK_SQL_CREATE_FIXTURES = 'sqlCreateFixtures',
    OTRA_TASK_HELP = 'help';
  // fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function test() : void
  {
    $this->expectOutputString(
      CLI_BASE .
      str_pad(self::OTRA_TASK_SQL_CREATE_FIXTURES, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Generates fixtures sql files and executes them. (sql_generate_fixtures)' .
      PHP_EOL . CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('databaseName', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::REQUIRED_PARAMETER .
      ') ' . CLI_INFO . 'The database name !' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('mask', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . '1 => We erase the database' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '2 => We clean the fixtures sql files and we erase the database.' . PHP_EOL .
      END_COLOR
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_SQL_CREATE_FIXTURES]
    );
  }
}
