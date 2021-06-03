<?php
declare(strict_types=1);

namespace src\console\deployment\taskFileOperation;

use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\cache\php\{BASE_PATH, CONSOLE_PATH, TEST_PATH};
use function otra\console\deployment\genBootstrap\getFileInfoFromRequiresAndExtends;

/**
 * @runTestsInSeparateProcesses
 */
class GetFileInfoFromRequiresAndExtendsTest extends TestCase
{
  private const
    LABEL_BASE_PATH = 'BASE_PATH',
    LABEL_SLASH = '\'';

  private const KEY_LEVEL = 'level',
    KEY_CONTENT_TO_ADD = 'contentToAdd',
    KEY_FILENAME = 'filename',
    KEY_FILES_TO_CONCAT = 'filesToConcat',
    KEY_PARSED_FILES = 'parsedFiles',
    KEY_CLASSES_FROM_FILE = 'classesFromFile',
    KEY_PARSED_CONSTANTS = 'parsedConstants',
    TEST_FILENAME_PHP = 'test_filename.php',
    TESTS_EXAMPLES_DEPLOYMENT_TEST_REQUIRE_PHP = 'tests/examples/deployment/testRequire.php',
    REQUIRE_MATCHED = PHP_EOL . 'require ' . self::LABEL_BASE_PATH . ' . ' . self::LABEL_SLASH .
      self::TESTS_EXAMPLES_DEPLOYMENT_TEST_REQUIRE_PHP . self::LABEL_SLASH . ';' . PHP_EOL;

  // fixes isolation related issues
  protected $preserveGlobalState = FALSE;

  protected function setUp(): void
  {
    parent::setUp();
    require CONSOLE_PATH . 'deployment/genBootstrap/taskFileOperation.php';
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testRequire_NoParsedConstants()
  {
    // context
    $exampleFileAbsolutePath = BASE_PATH . self::TESTS_EXAMPLES_DEPLOYMENT_TEST_REQUIRE_PHP;
    $contentToAdd = 'echo \'test\';' . self::REQUIRE_MATCHED . '$a = 4;';
    define('otra\console\deployment\genBootstrap\VERBOSE', 2);
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
    getFileInfoFromRequiresAndExtends($paramsArrayToPassAsReference);

    // testing
    self::assertEquals(
      1,
      $paramsArrayToPassAsReference[self::KEY_LEVEL],
      'Testing $level...'
    );
    self::assertEquals(
      $contentToAdd,
      $paramsArrayToPassAsReference[self::KEY_CONTENT_TO_ADD],
      'Testing $contentToAdd...'
    );
    self::assertEquals(
      self::TEST_FILENAME_PHP,
      $paramsArrayToPassAsReference[self::KEY_FILENAME],
      'Testing $filename...'
    );
    self::assertEquals(
      [
        'php' => [
          'require' =>
          [
            $exampleFileAbsolutePath => [
              'match' => self::REQUIRE_MATCHED,
              'posMatch' => 12
            ]
          ]
        ]
      ],
      $paramsArrayToPassAsReference[self::KEY_FILES_TO_CONCAT],
      'Testing $filesToConcat...'
    );
    self::assertEquals(
      [$exampleFileAbsolutePath],
      $paramsArrayToPassAsReference[self::KEY_PARSED_FILES],
      'Testing $parsedFiles...'
    );
    self::assertEquals(
      [],
      $paramsArrayToPassAsReference[self::KEY_CLASSES_FROM_FILE],
      'Testing $classesFromFile...'
    );
    self::assertEquals(
      [],
      $paramsArrayToPassAsReference[self::KEY_PARSED_CONSTANTS],
      'Testing $parsedConstants...'
    );
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testRequire_WithParsedConstants()
  {
    // context
    $exampleFileAbsolutePath = BASE_PATH . self::TESTS_EXAMPLES_DEPLOYMENT_TEST_REQUIRE_PHP;
    $contentToAdd = 'echo \'test\';' . self::REQUIRE_MATCHED . '$a = 4;';
    define('otra\console\deployment\genBootstrap\VERBOSE', 2);
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
    getFileInfoFromRequiresAndExtends($paramsArrayToPassAsReference);

    // testing
    self::assertEquals(
      1,
      $paramsArrayToPassAsReference[self::KEY_LEVEL],
      'Testing $level...'
    );
    self::assertEquals(
      $contentToAdd,
      $paramsArrayToPassAsReference[self::KEY_CONTENT_TO_ADD],
      'Testing $contentToAdd...'
    );
    self::assertEquals(
      self::TEST_FILENAME_PHP,
      $paramsArrayToPassAsReference[self::KEY_FILENAME],
      'Testing $filename...'
    );
    self::assertEquals(
      [
        'php' => [
          'require' =>
            [
              $exampleFileAbsolutePath => [
                'match' => self::REQUIRE_MATCHED,
                'posMatch' => 12
              ]
            ]
        ]
      ],
      $paramsArrayToPassAsReference[self::KEY_FILES_TO_CONCAT],
      'Testing $filesToConcat...'
    );
    self::assertEquals(
      [$exampleFileAbsolutePath],
      $paramsArrayToPassAsReference[self::KEY_PARSED_FILES],
      'Testing $parsedFiles...'
    );
    self::assertEquals(
      [],
      $paramsArrayToPassAsReference[self::KEY_CLASSES_FROM_FILE],
      'Testing $classesFromFile...'
    );
    self::assertEquals(
      [self::LABEL_BASE_PATH => BASE_PATH],
      $paramsArrayToPassAsReference[self::KEY_PARSED_CONSTANTS],
      'Testing $parsedConstants...'
    );
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testExtends_AlreadyParsed()
  {
    // context
    $contentToAdd = 'class TestExtendsController extends otra\Controller';
    define('otra\console\deployment\genBootstrap\VERBOSE', 2);
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
    getFileInfoFromRequiresAndExtends($paramsArrayToPassAsReference);

    // testing
    self::assertEquals(
      1,
      $paramsArrayToPassAsReference[self::KEY_LEVEL],
      'Testing $level...'
    );
    self::assertEquals(
      $contentToAdd,
      $paramsArrayToPassAsReference[self::KEY_CONTENT_TO_ADD],
      'Testing $contentToAdd...'
    );
    self::assertEquals(
      self::TEST_FILENAME_PHP,
      $paramsArrayToPassAsReference[self::KEY_FILENAME],
      'Testing $filename...'
    );
    self::assertEquals(
      [],
      $paramsArrayToPassAsReference[self::KEY_FILES_TO_CONCAT],
      'Testing $filesToConcat...'
    );
    self::assertEquals(
      [],
      $paramsArrayToPassAsReference[self::KEY_PARSED_FILES],
      'Testing $parsedFiles...'
    );
    self::assertEquals(
      ['otra\Controller'],
      $paramsArrayToPassAsReference[self::KEY_CLASSES_FROM_FILE],
      'Testing $classesFromFile...'
    );
    self::assertEquals(
      [],
      $paramsArrayToPassAsReference[self::KEY_PARSED_CONSTANTS],
      'Testing $parsedConstants...'
    );
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testExtends_NotAlreadyParsed()
  {
    // context
    $exampleFileAbsolutePath = TEST_PATH . 'examples/deployment/TestExtendsController.php';
    $contentToAdd = file_get_contents($exampleFileAbsolutePath);
    define('otra\console\deployment\genBootstrap\VERBOSE', 2);
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
    getFileInfoFromRequiresAndExtends($paramsArrayToPassAsReference);

    // testing
    self::assertEquals(
      1,
      $paramsArrayToPassAsReference[self::KEY_LEVEL],
      'Testing $level...'
    );
    self::assertEquals(
      $contentToAdd,
      $paramsArrayToPassAsReference[self::KEY_CONTENT_TO_ADD],
      'Testing $contentToAdd...'
    );
    self::assertEquals(
      self::TEST_FILENAME_PHP,
      $paramsArrayToPassAsReference[self::KEY_FILENAME],
      'Testing $filename...'
    );
    self::assertEquals(
      [
        'php' => [
          'extends' =>
            [
              BASE_PATH .  'src/Controller.php'
            ]
        ]
      ],
      $paramsArrayToPassAsReference[self::KEY_FILES_TO_CONCAT],
      'Testing $filesToConcat...'
    );
    self::assertEquals(
      [BASE_PATH .  'src/Controller.php'],
      $paramsArrayToPassAsReference[self::KEY_PARSED_FILES],
      'Testing $parsedFiles...'
    );
    self::assertEquals(
      [],
      $paramsArrayToPassAsReference[self::KEY_CLASSES_FROM_FILE],
      'Testing $classesFromFile...'
    );
    self::assertEquals(
      [],
      $paramsArrayToPassAsReference[self::KEY_PARSED_CONSTANTS],
      'Testing $parsedConstants...'
    );
  }
}
