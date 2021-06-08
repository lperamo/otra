<?php
declare(strict_types=1);

namespace src\console\helpAndTools\version;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\{CORE_PATH, OTRA_VERSION};
use const otra\console\END_COLOR;

/**
 * @runTestsInSeparateProcesses
 */
class VersionTaskTest extends TestCase
{
  private const
    TASK_VERSION = 'version',
    CLI_BGD_LIGHT_BLACK = "\e[48;2;40;40;40m",
    CLI_INFO_GREEN = "\e[38;2;185;215;255m",
    CLI_VERSION_COLOR = "\e[38;2;220;220;220m",
    BLUE_ON_LIGHT_BLACK = "\e[38;2;140;170;255m" . self::CLI_BGD_LIGHT_BLACK,
    LIGHTBLUE_ON_LIGHT_BLACK = self::CLI_INFO_GREEN . self::CLI_BGD_LIGHT_BLACK,
    END_PADDING = 10,
    COMMIT_LENGTH = 14,
    SPACE_BEFORE_COPYRIGHT = 22,
    TOTAL_WIDTH = 39,
    TOTAL_PLUS_END_PADDING = self::TOTAL_WIDTH + self::END_PADDING;

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testVersion() : void
  {
    // context
    require CORE_PATH . 'tools/getOtraCommitNumber.php';
    $byPeramoLionel = explode('*', "B*y* *P*é*r*a*m*o* *L*i*o*n*e*l*.");
    $endPaddingString = str_repeat(' ', self::END_PADDING);
    $blankLine = str_repeat(' ', self::TOTAL_PLUS_END_PADDING);
    $contentBeginning = self::CLI_BGD_LIGHT_BLACK . str_repeat(' ', self::TOTAL_PLUS_END_PADDING) . PHP_EOL .
      self::BLUE_ON_LIGHT_BLACK . " ..|''||   " . self::LIGHTBLUE_ON_LIGHT_BLACK . "|''||''| " . self::BLUE_ON_LIGHT_BLACK . "  '''|.   " . self::LIGHTBLUE_ON_LIGHT_BLACK . "    |    " . $endPaddingString .
      PHP_EOL
      . self::BLUE_ON_LIGHT_BLACK . ".|'    ||  " . self::LIGHTBLUE_ON_LIGHT_BLACK . " ' || '  " . self::BLUE_ON_LIGHT_BLACK . " ||   ||  " . self::LIGHTBLUE_ON_LIGHT_BLACK . "   |||   " . $endPaddingString .
      PHP_EOL
      . self::BLUE_ON_LIGHT_BLACK . "||      || " . self::LIGHTBLUE_ON_LIGHT_BLACK . "   ||    " . self::BLUE_ON_LIGHT_BLACK . "'||''|'   " . self::LIGHTBLUE_ON_LIGHT_BLACK . "  |  .|  " . $endPaddingString .
      PHP_EOL
      . self::BLUE_ON_LIGHT_BLACK . "'|.     || " . self::LIGHTBLUE_ON_LIGHT_BLACK . "   ||    " . self::BLUE_ON_LIGHT_BLACK . " ||   |.  " . self::LIGHTBLUE_ON_LIGHT_BLACK . " |''''|. " . $endPaddingString .
      PHP_EOL
      . self::BLUE_ON_LIGHT_BLACK . " ''|...|'  " . self::LIGHTBLUE_ON_LIGHT_BLACK . "  .||.   " . self::BLUE_ON_LIGHT_BLACK . ".||.  '|' " . self::LIGHTBLUE_ON_LIGHT_BLACK . ".'    '|'" . $endPaddingString . PHP_EOL .
      $blankLine . PHP_EOL .
      str_repeat(' ', self::SPACE_BEFORE_COPYRIGHT);

    foreach($byPeramoLionel as $index => $character)
    {
      $keyTwice = $index << 2;
      $contentBeginning .= "\e[38;2;" . (76 + $keyTwice) . ";" . (136 + $keyTwice) . ";" . (191 + $keyTwice) . "m" . $character;
    }

    $contentBeginning .= $endPaddingString . PHP_EOL .
      $blankLine . PHP_EOL .
      self::CLI_VERSION_COLOR . self::CLI_BGD_LIGHT_BLACK . 'Commit ';

    $contentEnding = str_pad(OTRA_VERSION, self::TOTAL_WIDTH - self::COMMIT_LENGTH, ' ', STR_PAD_LEFT) .
      $endPaddingString . PHP_EOL .
    self::CLI_BGD_LIGHT_BLACK . $blankLine . END_COLOR . PHP_EOL;

    // testing
    $this->expectOutputRegex(
      '@^' .
      preg_quote($contentBeginning, '@') .
      '\\w{7}' .
      preg_quote($contentEnding, '@') .
      '$@'
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::TASK_VERSION,
      ['otra.php', self::TASK_VERSION]
    );
  }
}
