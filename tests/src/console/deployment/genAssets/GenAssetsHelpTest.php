<?php
declare(strict_types=1);

namespace src\console\deployment\genAssets;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\console\
{CLI_BASE, CLI_GRAY, CLI_INFO, CLI_INFO_HIGHLIGHT, END_COLOR, STRING_PAD_FOR_OPTION_FORMATTING};
use const otra\bin\TASK_CLASS_MAP_PATH;


/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class GenAssetsHelpTest extends TestCase
{
  private const string
    OTRA_TASK_GEN_ASSETS = 'genAssets',
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
      str_pad(self::OTRA_TASK_GEN_ASSETS, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Generates one css file and one js file that contain respectively all the minified css files and all the obfuscated minified js files. Compresses the SVGs.' .
      PHP_EOL . CLI_INFO_HIGHLIGHT .
      self::LABEL_PLUS . str_pad('mask', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . '1 => templates' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '2 => CSS' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '4 => JS' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '8 => JSON manifest' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '16 => SVG' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '31 => all (default)' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      self::LABEL_PLUS . str_pad('js-level-compilation', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'Optimization level for Google Closure Compiler' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '0 for WHITESPACE_ONLY' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '1 for SIMPLE_OPTIMIZATIONS (default)' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '2 for ADVANCED_OPTIMIZATIONS' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      self::LABEL_PLUS . str_pad('route', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'The route for which you want to generate resources.' . PHP_EOL .
      END_COLOR
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_GEN_ASSETS]
    );
  }
}
