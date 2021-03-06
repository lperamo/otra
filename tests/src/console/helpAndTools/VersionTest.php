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
    TASKS_CLASSMAP_FILENAME = 'tasksClassMap.php',
    TASKS_CLASS_MAP = BASE_PATH . 'cache/php/' . self::TASKS_CLASSMAP_FILENAME,
    TASK_VERSION = 'version',
    OTRA_TASK_HELP = 'help',
    BLUE_ON_LIGHT_BLACK = CLI_BLUE . CLI_BGD_LIGHT_BLACK,
    LIGHTBLUE_ON_LIGHT_BLACK = CLI_LIGHT_BLUE . CLI_BGD_LIGHT_BLACK,
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
    str_repeat(' ', 40) . CLI_WHITE . CLI_BGD_LIGHT_BLACK . OTRA_VERSION . END_COLOR . PHP_EOL;

    // testing
    $this->expectOutputString(CLI_BGD_LIGHT_BLACK . str_repeat(' ', self::END_PADDING + self::INITIAL_ADDITIONAL_PADDING) . "\n" .
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
      require BASE_PATH . 'cache/php/tasksClassMap.php',
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
      CLI_WHITE .
      str_pad(self::TASK_VERSION, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_LIGHT_GRAY . ': ' . CLI_CYAN .
      'Shows the framework version.' .
      PHP_EOL . END_COLOR
    );

    TasksManager::execute(
      require self::TASKS_CLASS_MAP,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::TASK_VERSION]
    );
  }
}
