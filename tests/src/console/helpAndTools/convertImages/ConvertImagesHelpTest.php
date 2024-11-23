<?php
declare(strict_types=1);

namespace src\console\helpAndTools\convertImages;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\console\{CLI_BASE, CLI_GRAY, CLI_INFO, CLI_INFO_HIGHLIGHT, END_COLOR};
use const otra\bin\TASK_CLASS_MAP_PATH;

/**
 * @runTestsInSeparateProcesses
 */
class ConvertImagesHelpTest extends TestCase
{
  private const string
    OTRA_TASK_CONVERT_IMAGES = 'convertImages',
    OTRA_TASK_HELP = 'help';

  /**
   * @throws OtraException
   */
  public function test() : void
  {
    // testing
    $this->expectOutputString(
      CLI_BASE .
      str_pad(self::OTRA_TASK_CONVERT_IMAGES, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO . 'Converts images to another format.' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('source', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::REQUIRED_PARAMETER .
      ') ' . CLI_INFO . 'The source format' . PHP_EOL . CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('destination', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::REQUIRED_PARAMETER .
      ') ' . CLI_INFO . 'The destination format' . PHP_EOL . CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('quality', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'The percentage of quality. Defaults to 75.' . PHP_EOL . CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('keep', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'Put ' . CLI_INFO_HIGHLIGHT . 'true' . CLI_INFO .
      ' to keep the source image as a backup. Defaults to ' . CLI_INFO_HIGHLIGHT . 'true' . CLI_INFO .'.' . PHP_EOL .
      END_COLOR
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_CONVERT_IMAGES]
    );
  }
}
