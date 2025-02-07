<?php
declare(strict_types=1);

namespace src\console\deployment\genBootstrap\taskFileOperation;

use otra\OtraException;
use PHPUnit\Framework\TestCase;
use function otra\console\deployment\genBootstrap\getDependenciesFileInfo;
use const otra\cache\php\{BASE_PATH, CONSOLE_PATH, TEST_PATH};
use function otra\console\deployment\genBootstrap\getFileInfoFromRequiresAndExtends;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class GetFileInfoFromRequiresAndExtendsTest extends TestCase
{
  private const string
    CONST_NAME_OTRA_CONSOLE_DEPLOYMENT_GEN_BOOTSTRAP_VERBOSE = 'otra\\console\\deployment\\genBootstrap\\VERBOSE',
    KEY_LEVEL = 'level',
    KEY_CONTENT_TO_ADD = 'contentToAdd',
    KEY_FILENAME = 'filename',
    KEY_FILES_TO_CONCAT = 'filesToConcat',
    KEY_PARSED_FILES = 'parsedFiles',
    KEY_CLASSES_FROM_FILE = 'classesFromFile',
    KEY_PARSED_CONSTANTS = 'parsedConstants',
    LABEL_BASE_PATH = 'BASE_PATH',
    LABEL_SLASH = '\'',
    LABEL_TESTING_LEVEL = 'Testing $level...',
    LABEL_TESTING_CONTENT_TO_ADD = 'Testing $contentToAdd...',
    LABEL_TESTING_FILENAME = 'Testing $filename...',
    LABEL_TESTING_FILES_TO_CONCAT = 'Testing $filesToConcat...',
    LABEL_TESTING_PARSED_FILES = 'Testing $parsedFiles...',
    LABEL_TESTING_CLASSES_FROM_FILE = 'Testing $classesFromFile...',
    LABEL_TESTING_PARSED_CONSTANTS = 'Testing $parsedConstants...',
    REQUIRE_MATCHED = PHP_EOL . 'require ' . self::LABEL_BASE_PATH . ' . ' . self::LABEL_SLASH .
  self::TESTS_EXAMPLES_DEPLOYMENT_TEST_REQUIRE_PHP . self::LABEL_SLASH . ';' . PHP_EOL,
    TEST_FILENAME_PHP = 'test_filename.php',
    TESTS_EXAMPLES_DEPLOYMENT_TEST_REQUIRE_PHP = 'tests/examples/deployment/testRequire.php';

  protected function setUp(): void
  {
    parent::setUp();
    require CONSOLE_PATH . 'deployment/genBootstrap/taskFileOperation.php';
    require CONSOLE_PATH . 'deployment/genBootstrap/assembleFiles.php';
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testRequire_NoParsedConstants(): void
  {
    // context
    $exampleFileAbsolutePath = BASE_PATH . self::TESTS_EXAMPLES_DEPLOYMENT_TEST_REQUIRE_PHP;
    $contentToAdd = 'echo \'test\';' . self::REQUIRE_MATCHED . '$a = 4;';
    define(self::CONST_NAME_OTRA_CONSOLE_DEPLOYMENT_GEN_BOOTSTRAP_VERBOSE, 2);
    $paramsArrayToPassAsReference = [
      self::KEY_LEVEL => 1,
      self::KEY_CONTENT_TO_ADD => $contentToAdd,
      self::KEY_FILENAME => self::TEST_FILENAME_PHP,
      self::KEY_FILES_TO_CONCAT => [],
      self::KEY_PARSED_FILES => [],
      self::KEY_CLASSES_FROM_FILE => [],
      self::KEY_PARSED_CONSTANTS => []
    ];

    // launching
    getDependenciesFileInfo($paramsArrayToPassAsReference);

    // testing
    self::assertSame(
      1,
      $paramsArrayToPassAsReference[self::KEY_LEVEL],
      self::LABEL_TESTING_LEVEL
    );
    self::assertSame(
      $contentToAdd,
      $paramsArrayToPassAsReference[self::KEY_CONTENT_TO_ADD],
      self::LABEL_TESTING_CONTENT_TO_ADD
    );
    self::assertSame(
      self::TEST_FILENAME_PHP,
      $paramsArrayToPassAsReference[self::KEY_FILENAME],
      self::LABEL_TESTING_FILENAME
    );
    self::assertSame(
      [
        'php' => [
          'use' => [],
          'require' =>
          [
            $exampleFileAbsolutePath => [
              'match' => self::REQUIRE_MATCHED
            ]
          ],
          'extends' => [],
          'static' => []
        ]
      ],
      $paramsArrayToPassAsReference[self::KEY_FILES_TO_CONCAT],
      self::LABEL_TESTING_FILES_TO_CONCAT
    );
    self::assertSame(
      [$exampleFileAbsolutePath],
      $paramsArrayToPassAsReference[self::KEY_PARSED_FILES],
      self::LABEL_TESTING_PARSED_FILES
    );
    self::assertSame(
      [],
      $paramsArrayToPassAsReference[self::KEY_CLASSES_FROM_FILE],
      self::LABEL_TESTING_CLASSES_FROM_FILE
    );
    self::assertSame(
      [],
      $paramsArrayToPassAsReference[self::KEY_PARSED_CONSTANTS],
      self::LABEL_TESTING_PARSED_CONSTANTS
    );
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testRequire_WithParsedConstants(): void
  {
    // context
    $exampleFileAbsolutePath = BASE_PATH . self::TESTS_EXAMPLES_DEPLOYMENT_TEST_REQUIRE_PHP;
    $contentToAdd = 'echo \'test\';' . self::REQUIRE_MATCHED . '$a = 4;';
    define(self::CONST_NAME_OTRA_CONSOLE_DEPLOYMENT_GEN_BOOTSTRAP_VERBOSE, 2);
    $paramsArrayToPassAsReference = [
      self::KEY_LEVEL => 1,
      self::KEY_CONTENT_TO_ADD => $contentToAdd,
      self::KEY_FILENAME => self::TEST_FILENAME_PHP,
      self::KEY_FILES_TO_CONCAT => [],
      self::KEY_PARSED_FILES => [],
      self::KEY_CLASSES_FROM_FILE => [],
      self::KEY_PARSED_CONSTANTS => [self::LABEL_BASE_PATH => BASE_PATH]
    ];

    // launching
    getDependenciesFileInfo($paramsArrayToPassAsReference);

    // testing
    self::assertSame(
      1,
      $paramsArrayToPassAsReference[self::KEY_LEVEL],
      self::LABEL_TESTING_LEVEL
    );
    self::assertSame(
      $contentToAdd,
      $paramsArrayToPassAsReference[self::KEY_CONTENT_TO_ADD],
      self::LABEL_TESTING_CONTENT_TO_ADD
    );
    self::assertSame(
      self::TEST_FILENAME_PHP,
      $paramsArrayToPassAsReference[self::KEY_FILENAME],
      self::LABEL_TESTING_FILENAME
    );
    self::assertSame(
      [
        'php' => [
          'use' => [],
          'require' =>
            [
              $exampleFileAbsolutePath => ['match' => self::REQUIRE_MATCHED]
            ],
          'extends' => [],
          'static' => []
        ]
      ],
      $paramsArrayToPassAsReference[self::KEY_FILES_TO_CONCAT],
      self::LABEL_TESTING_FILES_TO_CONCAT
    );
    self::assertSame(
      [$exampleFileAbsolutePath],
      $paramsArrayToPassAsReference[self::KEY_PARSED_FILES],
      self::LABEL_TESTING_PARSED_FILES
    );
    self::assertSame(
      [],
      $paramsArrayToPassAsReference[self::KEY_CLASSES_FROM_FILE],
      self::LABEL_TESTING_CLASSES_FROM_FILE
    );
    self::assertSame(
      [self::LABEL_BASE_PATH => BASE_PATH],
      $paramsArrayToPassAsReference[self::KEY_PARSED_CONSTANTS],
      self::LABEL_TESTING_PARSED_CONSTANTS
    );
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testExtends_AlreadyParsed(): void
  {
    // context
    $contentToAdd = 'class TestExtendsController extends otra\Controller';
    define(self::CONST_NAME_OTRA_CONSOLE_DEPLOYMENT_GEN_BOOTSTRAP_VERBOSE, 2);
    $paramsArrayToPassAsReference = [
      self::KEY_LEVEL => 1,
      self::KEY_CONTENT_TO_ADD => $contentToAdd,
      self::KEY_FILENAME => self::TEST_FILENAME_PHP,
      self::KEY_FILES_TO_CONCAT => [],
      self::KEY_PARSED_FILES => [],
      self::KEY_CLASSES_FROM_FILE => ['otra\Controller'],
      self::KEY_PARSED_CONSTANTS => []
    ];

    // launching
    getDependenciesFileInfo($paramsArrayToPassAsReference);

    // testing
    self::assertSame(
      1,
      $paramsArrayToPassAsReference[self::KEY_LEVEL],
      self::LABEL_TESTING_LEVEL
    );
    self::assertSame(
      $contentToAdd,
      $paramsArrayToPassAsReference[self::KEY_CONTENT_TO_ADD],
      self::LABEL_TESTING_CONTENT_TO_ADD
    );
    self::assertSame(
      self::TEST_FILENAME_PHP,
      $paramsArrayToPassAsReference[self::KEY_FILENAME],
      self::LABEL_TESTING_FILENAME
    );
    self::assertSame(
      [],
      $paramsArrayToPassAsReference[self::KEY_FILES_TO_CONCAT],
      self::LABEL_TESTING_FILES_TO_CONCAT
    );
    self::assertSame(
      [],
      $paramsArrayToPassAsReference[self::KEY_PARSED_FILES],
      self::LABEL_TESTING_PARSED_FILES
    );
    self::assertSame(
      ['otra\Controller'],
      $paramsArrayToPassAsReference[self::KEY_CLASSES_FROM_FILE],
      self::LABEL_TESTING_CLASSES_FROM_FILE
    );
    self::assertSame(
      [],
      $paramsArrayToPassAsReference[self::KEY_PARSED_CONSTANTS],
      self::LABEL_TESTING_PARSED_CONSTANTS
    );
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testExtends_NotAlreadyParsed(): void
  {
    // context
    $exampleFileAbsolutePath = TEST_PATH . 'examples/deployment/TestExtendsController.php';
    $contentToAdd = file_get_contents($exampleFileAbsolutePath);
    define(self::CONST_NAME_OTRA_CONSOLE_DEPLOYMENT_GEN_BOOTSTRAP_VERBOSE, 2);
    $paramsArrayToPassAsReference = [
      self::KEY_LEVEL => 1,
      self::KEY_CONTENT_TO_ADD => $contentToAdd,
      self::KEY_FILENAME => self::TEST_FILENAME_PHP,
      self::KEY_FILES_TO_CONCAT => [],
      self::KEY_PARSED_FILES => [],
      self::KEY_CLASSES_FROM_FILE => [],
      self::KEY_PARSED_CONSTANTS => []
    ];

    // launching
    getDependenciesFileInfo($paramsArrayToPassAsReference);

    // testing
    self::assertSame(
      1,
      $paramsArrayToPassAsReference[self::KEY_LEVEL],
      self::LABEL_TESTING_LEVEL
    );
    self::assertSame(
      $contentToAdd,
      $paramsArrayToPassAsReference[self::KEY_CONTENT_TO_ADD],
      self::LABEL_TESTING_CONTENT_TO_ADD
    );
    self::assertSame(
      self::TEST_FILENAME_PHP,
      $paramsArrayToPassAsReference[self::KEY_FILENAME],
      self::LABEL_TESTING_FILENAME
    );
    self::assertSame(
      [
        'php' => [
          'extends' =>
            [
              BASE_PATH .  'src/Controller.php'
            ]
        ]
      ],
      $paramsArrayToPassAsReference[self::KEY_FILES_TO_CONCAT],
      self::LABEL_TESTING_FILES_TO_CONCAT
    );
    self::assertSame(
      [BASE_PATH .  'src/Controller.php'],
      $paramsArrayToPassAsReference[self::KEY_PARSED_FILES],
      self::LABEL_TESTING_PARSED_FILES
    );
    self::assertSame(
      [],
      $paramsArrayToPassAsReference[self::KEY_CLASSES_FROM_FILE],
      self::LABEL_TESTING_CLASSES_FROM_FILE
    );
    self::assertSame(
      [],
      $paramsArrayToPassAsReference[self::KEY_PARSED_CONSTANTS],
      self::LABEL_TESTING_PARSED_CONSTANTS
    );
  }
}
