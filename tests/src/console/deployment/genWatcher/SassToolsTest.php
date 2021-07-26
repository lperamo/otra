<?php
declare(strict_types=1);

namespace src\console\deployment\genWatcher;

use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\cache\php\{BUNDLES_PATH, CONSOLE_PATH, CORE_PATH, TEST_PATH};
use const otra\console\deployment\genWatcher\{KEY_ALL_SASS, SASS_TREE_STRING_INIT};
use function otra\console\deployment\genWatcher\searchSassLastLeaves;
use function otra\tools\debug\dump;

/**
 * @runTestsInSeparateProcesses
 */
class SassToolsTest extends TestCase
{
  private const
    EXAMPLES_SCSS_PATH = TEST_PATH . 'examples/deployment/resources/scss/',
    SCSS_MAIN_PATH = self::EXAMPLES_SCSS_PATH . 'main.scss',
    SCSS_MAIN2_PATH = self::EXAMPLES_SCSS_PATH . 'main2.scss',
    SCSS_MAIN_DEPENDENCY_LVL0_PATH = self::EXAMPLES_SCSS_PATH . '_dependency.scss',
    SCSS_MAIN_DEPENDENCY_LVL1_PATH = self::EXAMPLES_SCSS_PATH . '_dependency3.scss',
    SCSS_MAIN_DEPENDENCY2_LVL0_2_PATH = self::EXAMPLES_SCSS_PATH . '_dependency2.scss',
    BUNDLES_CONFIG_PATH = BUNDLES_PATH . 'config/',
    ROUTES_CONFIG_PATH = self::BUNDLES_CONFIG_PATH . 'Routes.php',
    DOT_EXTENSION = '.scss',
    LABEL_TESTING_THE_TREE = 'Testing the sass tree... We have :';

  // fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  private function searchSassLastLeavesContext()
  {
    // -- preparing the architecture
    mkdir(self::BUNDLES_CONFIG_PATH, 0777, true);
    file_put_contents(self::ROUTES_CONFIG_PATH, '<?php declare(strict_types=1); return [];');

    // -- defining constants and variables - part 2
    define(__NAMESPACE__ . '\\APP_ENV', 'APP_ENV');
    $_SERVER[APP_ENV] = 'prod';
    $argv = [];

    // -- including needed libraries
    require CORE_PATH . 'tools/debug/dump.php'; // only for debugging purposes
    require CONSOLE_PATH . 'deployment/genWatcher/sassTools.php';
    require CONSOLE_PATH . 'deployment/taskFileInit.php';
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testSearchSassLastLeavesOneMainSass() : void
  {
    // context
    // Defining variables - part 1
    $this->searchSassLastLeavesContext();

    // -- the tree must already contains the main sass file... before calling the tool
    $sassTree = [KEY_ALL_SASS => [self::SCSS_MAIN_PATH => true], 1=>[], 2=>[]];

    // -- defining constants and variables - part 2
    $sassTreeString = SASS_TREE_STRING_INIT;

    // running
    searchSassLastLeaves(
      $sassTree,
      self::SCSS_MAIN_PATH,
      self::SCSS_MAIN_PATH,
      self::DOT_EXTENSION,
      $sassTreeString
    );

    // testing
    self::assertEquals(
      [
        0 => [
          self::SCSS_MAIN_PATH => true,
          self::SCSS_MAIN_DEPENDENCY_LVL0_PATH => true,
          self::SCSS_MAIN_DEPENDENCY_LVL1_PATH => true,
          self::SCSS_MAIN_DEPENDENCY2_LVL0_2_PATH => true
        ],
        1 => [
          1 => [0 => 0],
          2 => [0 => 0],
          3 => [0 => 0],
        ],
        2 => [
          0 => [
            1 => [
              2 => [3 => []]
            ],
            3 => []
          ],
        ]
      ],
      $sassTree,
      self::LABEL_TESTING_THE_TREE . PHP_EOL . dump($sassTree)
    );

    // cleaning
    unlink(self::ROUTES_CONFIG_PATH);
    rmdir(self::BUNDLES_CONFIG_PATH);
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testSearchSassLastLeavesTwoMainSass() : void
  {
    // context
    // Defining variables - part 1
    $this->searchSassLastLeavesContext();

    // -- the tree must already contains the main sass file... before calling the tool
    $sassTree = [KEY_ALL_SASS => [self::SCSS_MAIN_PATH => true], 1=>[], 2=>[]];

    // -- defining constants and variables - part 2
    $sassTreeString = SASS_TREE_STRING_INIT;

    // running
    searchSassLastLeaves(
      $sassTree,
      self::SCSS_MAIN_PATH,
      self::SCSS_MAIN_PATH,
      self::DOT_EXTENSION,
      $sassTreeString
    );

    // prepare data for the second main sass file
    $sassTree[0][self::SCSS_MAIN2_PATH ] = true;
    $sassTreeString = SASS_TREE_STRING_INIT;

    searchSassLastLeaves(
      $sassTree,
      self::SCSS_MAIN2_PATH,
      self::SCSS_MAIN2_PATH,
      self::DOT_EXTENSION,
      $sassTreeString
    );

    // testing
    self::assertEquals(
      [
        0 => [
          self::SCSS_MAIN_PATH => true,
          self::SCSS_MAIN_DEPENDENCY_LVL0_PATH => true,
          self::SCSS_MAIN_DEPENDENCY_LVL1_PATH => true,
          self::SCSS_MAIN_DEPENDENCY2_LVL0_2_PATH => true,
          self::SCSS_MAIN2_PATH => true
        ],
        1 => [
          1 => [0 => 0],
          2 => [0 => 0, 1 => 4],
          3 => [0 => 0, 1 => 4],
        ],
        2 => [
          0 => [
            1 => [
              2 => [3 => []]
            ],
            3 => []
          ],
          4 => [
            2 => [3 => []]
          ],
        ]
      ],
      $sassTree,
      self::LABEL_TESTING_THE_TREE . PHP_EOL . dump($sassTree)
    );

    // cleaning
    unlink(self::ROUTES_CONFIG_PATH);
    rmdir(self::BUNDLES_CONFIG_PATH);
  }
}
