<?php
declare(strict_types=1);

namespace src\console\helpAndTools;

use otra\console\TasksManager;
use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class RequirementsTest extends TestCase
{
  private const
    TASKS_CLASSMAP_FILENAME = 'tasksClassMap.php',
    TASKS_CLASS_MAP = BASE_PATH . 'cache/php/init/' . self::TASKS_CLASSMAP_FILENAME,
    TASK_REQUIREMENTS = 'requirements',
    OTRA_TASK_HELP = 'help',
    REQUIREMENTS_PADDING = 30;

  /**
   * @param string $requirement
   * @param string $description
   *
   * @return string
   */
  private function showRequirement(string $requirement, string $description) : string
  {
    return preg_quote(ADD_BOLD) . '(' . preg_quote(CLI_GREEN) . '|' . preg_quote(CLI_RED) . ')\s\s✔|⨯\s\s' .
      preg_quote(REMOVE_BOLD_INTENSITY . CLI_LIGHT_BLUE) .
      str_pad($requirement . ' ', self::REQUIREMENTS_PADDING, '.') . '\s' . $description
      . '\s';
  }

  /**
   * @author Lionel Péramo
   */
  public function testRequirements() : void
  {
    self::expectOutputRegex('@' .
      preg_quote(ADD_BOLD . CLI_BOLD_LIGHT_CYAN) . '  Requirements\s' .
      '\s\s-{12}' . preg_quote(REMOVE_BOLD_INTENSITY) . '\s\s' .
      preg_quote(CLI_LIGHT_BLUE) .
      $this->showRequirement(
        'JAVA',
        'Software platform => https://www.java.com. Only needed for optimizations with Google Closure Compiler.'
      ) .
      $this->showRequirement(
        'Typescript',
        'Only needed to contribute. TypeScript is a typed superset of JavaScript that compiles to plain JavaScript. => http://www.typescriptlang.org/'
      ) .
      $this->showRequirement(
        'SASS/SCSS',
        'Only needed to contribute. It is a stylesheet language that\'s compiled to CSS => https://sass-lang.com/'
      ) .
      $this->showRequirement(
        'PHP extension \'fileinfo\'',
        'Needed for analyzing MIME types'
      ) .
      $this->showRequirement(
        'PHP extension \'json\'',
        'Needed for encoding/decoding JSON format. \(needed by the developer toolbar\)'
      ) .
      $this->showRequirement(
        'PHP extension \'mbstring\'',
        'Needed for string multibyte functions'
      ) .
      $this->showRequirement(
        'PHP extension \'inotify\'',
        preg_quote(CLI_LIGHT_CYAN) . '\[Optional\]' . preg_quote(CLI_LIGHT_BLUE) .' Needed for OTRA watcher on unix like systems.'
      ) .
      $this->showRequirement(
        'PHP extension \'zend-opcache\'',
        preg_quote(CLI_LIGHT_CYAN) . '[Optional]' . preg_quote(CLI_LIGHT_BLUE) .' Needeed to use the preloading feature available since PHP 7.4'
      ) .
      $this->showRequirement(
        'PHP version 7.4.x+',
        'PHP version must be at least 7.4.x.'
      ) .
      '\s@'
    );

    // launching
    TasksManager::execute(
      require self::TASKS_CLASS_MAP,
      self::TASK_REQUIREMENTS,
      ['otra.php', self::TASK_REQUIREMENTS]
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testRequirementsHelp()
  {
    $this->expectOutputString(
      CLI_WHITE .
      str_pad(self::TASK_REQUIREMENTS, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_LIGHT_GRAY . ': ' . CLI_CYAN .
      'Shows the requirements to use OTRA at its maximum capabilities.' .
      PHP_EOL . END_COLOR
    );

    TasksManager::execute(
      require self::TASKS_CLASS_MAP,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::TASK_REQUIREMENTS]
    );
  }
}
