<?php
declare(strict_types=1);

namespace src\console\helpAndTools;

use otra\console\TasksManager;
use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class VersionTest extends TestCase
{
  private const
    TASK_VERSION = 'version',
    CLI_BGD_LIGHT_BLACK = "\e[48;2;40;40;40m",
    CLI_INFO_GREEN = "\e[38;2;185;215;255m",
    CLI_VERSION_COLOR = "\e[38;2;220;220;220m",
    OTRA_TASK_HELP = 'help',
    BLUE_ON_LIGHT_BLACK = "\e[38;2;140;170;255m" . self::CLI_BGD_LIGHT_BLACK,
    LIGHTBLUE_ON_LIGHT_BLACK = self::CLI_INFO_GREEN . self::CLI_BGD_LIGHT_BLACK,
    END_PADDING = 21,
    INITIAL_ADDITIONAL_PADDING = 39;

  /**
   * @author Lionel Péramo
   */
  public function testVersion() : void
  {
    // testing
    $byPeramoLionel = explode('*', "B*y* *P*é*r*a*m*o* *L*i*o*n*e*l*.");
    $content = '';

    foreach($byPeramoLionel as $index => &$character)
    {
      $keyTwice = $index << 2;
      $content .= "\e[38;2;" . (76 + $keyTwice) . ";" . (136 + $keyTwice) . ";" . (191 + $keyTwice) . "m" . $character;
    }

    $content .= str_repeat(' ', 20) . PHP_EOL .
    str_repeat(' ', 60) . PHP_EOL .
    str_repeat(' ', 40) . self::CLI_VERSION_COLOR . self::CLI_BGD_LIGHT_BLACK . OTRA_VERSION . END_COLOR . PHP_EOL;

    // testing
    $this->expectOutputString(self::CLI_BGD_LIGHT_BLACK . str_repeat(' ', self::END_PADDING + self::INITIAL_ADDITIONAL_PADDING) . "\n" .
      self::BLUE_ON_LIGHT_BLACK . " ..|''||   " . self::LIGHTBLUE_ON_LIGHT_BLACK . "|''||''| " . self::BLUE_ON_LIGHT_BLACK . "  '''|.   " . self::LIGHTBLUE_ON_LIGHT_BLACK . "    |    " . str_repeat(' ', self::END_PADDING) .
      PHP_EOL
      . self::BLUE_ON_LIGHT_BLACK . ".|'    ||  " . self::LIGHTBLUE_ON_LIGHT_BLACK . " ' || '  " . self::BLUE_ON_LIGHT_BLACK . " ||   ||  " . self::LIGHTBLUE_ON_LIGHT_BLACK . "   |||   " . str_repeat(' ', self::END_PADDING) .
      PHP_EOL
      . self::BLUE_ON_LIGHT_BLACK . "||      || " . self::LIGHTBLUE_ON_LIGHT_BLACK . "   ||    " . self::BLUE_ON_LIGHT_BLACK . "'||''|'   " . self::LIGHTBLUE_ON_LIGHT_BLACK . "  |  .|  " . str_repeat(' ', self::END_PADDING) .
      PHP_EOL
      . self::BLUE_ON_LIGHT_BLACK . "'|.     || " . self::LIGHTBLUE_ON_LIGHT_BLACK . "   ||    " . self::BLUE_ON_LIGHT_BLACK . " ||   |.  " . self::LIGHTBLUE_ON_LIGHT_BLACK . " |''''|. " . str_repeat(' ', self::END_PADDING) .
      PHP_EOL
      . self::BLUE_ON_LIGHT_BLACK . " ''|...|'  " . self::LIGHTBLUE_ON_LIGHT_BLACK . "  .||.   " . self::BLUE_ON_LIGHT_BLACK . ".||.  '|' " . self::LIGHTBLUE_ON_LIGHT_BLACK . ".'    '|'" . str_repeat(' ', self::END_PADDING) . "
                                                            
                       " . $content);

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::TASK_VERSION,
      ['otra.php', self::TASK_VERSION]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testVersionHelp()
  {
    $this->expectOutputString(
      CLI_BASE .
      str_pad(self::TASK_VERSION, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Shows the framework version.' .
      PHP_EOL . END_COLOR
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::TASK_VERSION]
    );
  }
}
