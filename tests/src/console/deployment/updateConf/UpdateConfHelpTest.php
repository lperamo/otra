<?php
declare(strict_types=1);

namespace src\console\deployment\updateConf;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\console\
{CLI_BASE,
  CLI_GRAY,
  CLI_INFO,
  CLI_INFO_HIGHLIGHT,
  END_COLOR,
  STRING_PAD_FOR_OPTION_FORMATTING};
use const otra\bin\TASK_CLASS_MAP_PATH;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class UpdateConfHelpTest extends TestCase
{
  private const
    OTRA_TASK_UPDATE_CONF = 'updateConf',
    OTRA_TASK_HELP = 'help';

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function test() : void
  {
    $this->expectOutputString(
      CLI_BASE .
      str_pad(self::OTRA_TASK_UPDATE_CONF, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Updates the files related to bundles and routes : schemas, routes, securities.' .
      PHP_EOL . CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('mask', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . '1 => Config.php files' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '2 => routes' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '4 => securities' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '8 => schema.yml' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '16 => fixtures' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '31 => All (Default)' .
      PHP_EOL . CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('route', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'To update only security files related to one specific route' . PHP_EOL .
      END_COLOR
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_UPDATE_CONF]
    );
  }
}
