<?php
declare(strict_types=1);

namespace src\console\deployment\genWatcher;

use otra\OtraException;
use PHPUnit\Framework\TestCase;

use ReflectionException;
use const otra\cache\php\{BUNDLES_PATH, CACHE_PATH, CONSOLE_PATH, CORE_PATH, TEST_PATH};
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT};
use const otra\console\deployment\genWatcher\{KEY_ALL_SASS, KEY_FULL_TREE, SASS_TREE_CACHE_PATH, SASS_TREE_STRING_INIT};

use function otra\console\deployment\genWatcher\
{getCssPathFromImport,
  createPrunedFullTree,
  saveSassTree,
  searchSassLastLeaves,
  updateSassTree,
  updateSassTreeAfterEvent};

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class SassToolsTest extends TestCase
{
  private const array BIG_TREE = [
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
  ];

  private const string
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

  protected function setUp(): void
  {
    parent::setUp();
    define('otra\\console\\deployment\\genWatcher\\SASS_TREE_CACHE_PATH',  CACHE_PATH . 'css/sassTree.php');
    require CORE_PATH . 'tools/debug/dump.php'; // only for debugging purposes
    require CONSOLE_PATH . 'deployment/genWatcher/sassTools.php';
  }

  protected function tearDown(): void
  {
    parent::tearDown();

    if (file_exists(SASS_TREE_CACHE_PATH))
      unlink(SASS_TREE_CACHE_PATH);

    if (file_exists(self::ROUTES_CONFIG_PATH))
    {
      unlink(self::ROUTES_CONFIG_PATH);
      rmdir(self::BUNDLES_CONFIG_PATH);
    }
  }

  private function searchSassLastLeavesContext(): void
  {
    // -- preparing the architecture
    mkdir(self::BUNDLES_CONFIG_PATH, 0777, true);
    file_put_contents(self::ROUTES_CONFIG_PATH, '<?php declare(strict_types=1); return [];');

    // -- defining constants and variables - part 2
    define(__NAMESPACE__ . '\\APP_ENV', 'APP_ENV');
    $_SERVER[APP_ENV] = 'prod';
    $argumentsVector = [];

    // -- including necessary libraries
    require CONSOLE_PATH . 'deployment/taskFileInit.php';
  }


  public function testCreatePrunedFullTree(): void
  {
    // launching
    // 0 equals to array_search(self::SCSS_MAIN_PATH, $sassTreeKeys)
    $prunedArray = createPrunedFullTree(1, self::BIG_TREE[KEY_FULL_TREE][0]);

    // testing
    self::assertSame(
      [2 => []],
      $prunedArray
    );
  }

  public function testGetCssPathFromImport(): void
  {
    // context
    $partialPath = CORE_PATH . 'resources/scss/pages/templateStructure/';
    $colors = 'colors';
    $dotExtension = '.scss';
    $fileName = $colors . $dotExtension;

    // launching
    [$newResourceToAnalyze, $absoluteImportPathWithDots, $absoluteImportPathWithDotsAlt] =
      getCssPathFromImport(
        $colors,
        $dotExtension,
        $partialPath
      );

    // testing
    self::assertSame(
      $partialPath . '_' . $fileName,
      $newResourceToAnalyze,
      'Testing $newResourceToAnalyze ...'
    );
    self::assertSame(
      $partialPath . $fileName,
      $absoluteImportPathWithDots,
      'Testing $absoluteImportPathWithDots ...'
    );
    self::assertSame(
      $partialPath . '_' . $fileName,
      $absoluteImportPathWithDotsAlt,
      'Testing $absoluteImportPathWithDotsAlt ...'
    );
  }

  /**
   * @throws OtraException|ReflectionException
   */
  public function testSaveSassTree(): void
  {
    // context
    // If there is already a cache for the sass tree, we remove it to be sure it will not interfere with our test
    if (file_exists(SASS_TREE_CACHE_PATH))
      unlink(SASS_TREE_CACHE_PATH);

    define(__NAMESPACE__ . '\\CACHED_SASS_TREE', TEST_PATH . 'examples/deployment/sassTree.php');

    // launching
    saveSassTree(self::BIG_TREE);

    // testing
    static::assertFileExists(SASS_TREE_CACHE_PATH);
    static::assertFileEquals(
      SASS_TREE_CACHE_PATH,
      CACHED_SASS_TREE,
    'Testing ' . CLI_INFO_HIGHLIGHT . SASS_TREE_CACHE_PATH . CLI_ERROR . ' against ' . CLI_INFO_HIGHLIGHT .
    CACHED_SASS_TREE . CLI_ERROR . ';'
    );
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testSearchSassLastLeaves_OneMainSass() : void
  {
    // context
    // Defining variables - part 1
    $this->searchSassLastLeavesContext();

    // -- the tree must already contain the main sass file... before calling the tool
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
    self::assertSame(
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
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testSearchSassLastLeaves_TwoMainSass() : void
  {
    // context
    // Defining variables - part 1
    $this->searchSassLastLeavesContext();

    // -- the tree must already contain the main sass file... before calling the tool
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
    self::assertSame(
      self::BIG_TREE,
      $sassTree,
      self::LABEL_TESTING_THE_TREE . PHP_EOL . dump($sassTree)
    );
  }

  public function testUpdateSassTree(): void
  {
    // context
    $sassTree = self::BIG_TREE;

    // launching
    updateSassTree($sassTree, self::SCSS_MAIN_PATH, self::SCSS_MAIN_DEPENDENCY_LVL0_PATH);

    // testing
    self::assertSame(
      self::BIG_TREE,
      $sassTree,
      'Testing $sassTree...'
    );
  }

  /**
   * Tests when it is a dependency (so a file beginning by '_' is updated).
   *
   * @throws OtraException
   */
  public function testUpdateSassTreeAfterEvent(): void
  {
    // context
    $this->searchSassLastLeavesContext();
    $sassTree = self::BIG_TREE;
    $sassTreeKeys = array_keys($sassTree[KEY_ALL_SASS]);

    // launching
    foreach ($sassTree[KEY_FULL_TREE] as $importingFile => &$importedFiles)
    {
      updateSassTreeAfterEvent(
        $sassTree,
        $sassTreeKeys,
        'scss',
        0, // array_search(self::SCSS_MAIN_PATH, $sassTreeKeys)
        $importingFile,
        $importedFiles,
        1,
        [1] // [array_search(self::SCSS_MAIN_DEPENDENCY_LVL0_PATH, $sassTreeKeys)]
      );
    }

    // testing
    self::assertSame(
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
          3 => [1 => 4],
        ],
        2 => [
          0 => [
            1 => [
              2 => [3 => []]
            ]
          ],
          4 => [
            2 => [3 => []]
          ],
        ]
      ],
      $sassTree,
      'Testing if $sassTree has correctly been udpated...'
    );
  }
}
