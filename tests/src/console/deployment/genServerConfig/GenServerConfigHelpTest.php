<?php
declare(strict_types=1);

namespace src\console\deployment\genServerConfig;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\{DEV, PROD};
use const otra\console\{CLI_BASE, CLI_GRAY, CLI_INFO, CLI_INFO_HIGHLIGHT, CLI_WARNING, END_COLOR};

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class GenServerConfigHelpTest extends TestCase
{
  private const string
    OTRA_TASK_GEN_SERVER_CONFIG = 'genServerConfig',
    OTRA_TASK_HELP = 'help',
    LABEL_PLUS = '   + ';

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function test() : void
  {
    $this->expectOutputString(
      CLI_BASE .
      str_pad(self::OTRA_TASK_GEN_SERVER_CONFIG, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Generates a server configuration adapted to OTRA.' .
      PHP_EOL . CLI_INFO_HIGHLIGHT .
      self::LABEL_PLUS . str_pad('filename', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::REQUIRED_PARAMETER .
      ') ' . CLI_INFO . 'Name of the file to put the generated configuration' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      self::LABEL_PLUS . str_pad('env', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'Target environment : ' . CLI_INFO_HIGHLIGHT . DEV . CLI_INFO . ' (default) or ' . CLI_INFO_HIGHLIGHT . PROD . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      self::LABEL_PLUS . str_pad('tech', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . CLI_INFO_HIGHLIGHT . 'nginx' . CLI_INFO . ' (default) or ' . CLI_INFO_HIGHLIGHT . 'apache' .
      CLI_WARNING . ' (but works only for Nginx for now)' . PHP_EOL .
      END_COLOR
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_GEN_SERVER_CONFIG]
    );
  }
}
