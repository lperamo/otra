<?php
declare(strict_types=1);

namespace src\console\helpAndTools\requirements;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\console\{ADD_BOLD, CLI_ERROR, CLI_INFO, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, REMOVE_BOLD_INTENSITY};
use const otra\bin\TASK_CLASS_MAP_PATH;

/**
 * @runTestsInSeparateProcesses
 */
class RequirementsTaskTest extends TestCase
{
  private const
    TASK_REQUIREMENTS = 'requirements',
    REQUIREMENTS_PADDING = 30;

  /**
   * @param string $requirement
   * @param string $description
   *
   * @return string
   */
  private function showRequirement(string $requirement, string $description) : string
  {
    return preg_quote(ADD_BOLD) . '(' . preg_quote(CLI_SUCCESS) . '|' . preg_quote(CLI_ERROR) . ')\s\s✔|⨯\s\s' .
      preg_quote(REMOVE_BOLD_INTENSITY . CLI_INFO) .
      str_pad($requirement . ' ', self::REQUIREMENTS_PADDING, '.') . '\s' . $description
      . '\s';
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testRequirements() : void
  {
    self::expectOutputRegex('@' .
      preg_quote(ADD_BOLD . CLI_INFO_HIGHLIGHT) . '  Requirements\s' .
      '\s\s-{12}' . preg_quote(REMOVE_BOLD_INTENSITY) . '\s\s' .
      preg_quote(CLI_INFO) .
      $this->showRequirement(
        'JAVA',
        'Software platform => https://www.java.com. Only needed for optimizations with Google Closure Compiler.'
      ) .
      $this->showRequirement(
        'Typescript',
        'Only needed to contribute. TypeScript is a typed superset of JavaScript that compiles to plain JavaScript. => https://www.typescriptlang.org/'
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
        preg_quote(CLI_INFO_HIGHLIGHT) . '\[Optional\]' . preg_quote(CLI_INFO) .' Needed for OTRA watcher on unix like systems.'
      ) .
      $this->showRequirement(
        'PHP extension \'zend-opcache\'',
        preg_quote(CLI_INFO_HIGHLIGHT) . '[Optional]' . preg_quote(CLI_INFO) .' Needeed to use the preloading feature available since PHP 7.4'
      ) .
      $this->showRequirement(
        'PHP version 7.4.x+',
        'PHP version must be at least 7.4.x.'
      ) .
      '\s@'
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::TASK_REQUIREMENTS,
      ['otra.php', self::TASK_REQUIREMENTS]
    );
  }
}
