<?php
declare(strict_types=1);

namespace src\console\deployment\taskFileOperation;

use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\cache\php\{BASE_PATH, CONSOLE_PATH, TEST_PATH};
use function otra\console\deployment\genBootstrap\getFileInfoFromRequiresAndExtends;
use function otra\tools\copyFileAndFolders;

/**
 * @runTestsInSeparateProcesses
 */
class getFileInfoFromRequiresAndExtendsTest extends TestCase
{
  private const
    FILE_TO_COMPRESS = TEST_PATH . 'examples/deployment/FileToCompress.php',
    COMPRESSED_FILE = TEST_PATH . 'examples/deployment/CompressedFile',
    FINAL_COMPRESSED_FILE = self::COMPRESSED_FILE . '.php',
    BACKUP_COMPRESSED_FILE = TEST_PATH . 'examples/deployment/BackupCompressedFile.php';

  // fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  /**
   * @throws OtraException
   */
  protected function setUp(): void
  {
    parent::setUp();
    require CONSOLE_PATH . 'deployment/genBootstrap/taskFileOperation.php';
//    require CORE_PATH . 'tools/copyFilesAndFolders.php';
//    copyFileAndFolders(
//      [TEST_PATH . 'examples/deployment/BackupFileToCompress.php'],
//      [self::FILE_TO_COMPRESS]
//    );
  }

  protected function tearDown(): void
  {
    parent::tearDown();

    // cleaning
//    unlink(self::FINAL_COMPRESSED_FILE);
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testRequire_NoParsedConstants()
  {
    // context
    $filename = 'test_filename.php';
    $exampleFile = 'tests/examples/deployment/testRequire.php';
    $exampleFileAbsolutePath = BASE_PATH . $exampleFile;
    $requireMatched = PHP_EOL . 'require BASE_PATH . \'' . $exampleFile . '\';' . PHP_EOL;
    $contentToAdd = 'echo \'test\';' . $requireMatched . '$a = 4;';
    define('otra\console\deployment\genBootstrap\VERBOSE', 2);
    $paramsArrayToPassAsReference = [
      'level' => 1,
      'contentToAdd' => $contentToAdd,
      'filename' => $filename,
      'filesToConcat' => [],
      'parsedFiles' => [],
      'classesFromFile' => [],
      'parsedConstants' => []
    ];

    // launching
    getFileInfoFromRequiresAndExtends($paramsArrayToPassAsReference);

    // testing
    self::assertEquals(
      1,
      $paramsArrayToPassAsReference['level'],
      'Testing $level...'
    );
    self::assertEquals(
      $contentToAdd,
      $paramsArrayToPassAsReference['contentToAdd'],
      'Testing $contentToAdd...'
    );
    self::assertEquals(
      $filename,
      $paramsArrayToPassAsReference['filename'],
      'Testing $filename...'
    );
    self::assertEquals(
      [
        'php' => [
          'require' =>
          [
            $exampleFileAbsolutePath => [
              'match' => $requireMatched,
              'posMatch' => 12
            ]
          ]
        ]
      ],
      $paramsArrayToPassAsReference['filesToConcat'],
      'Testing $filesToConcat...'
    );
    self::assertEquals(
      [$exampleFileAbsolutePath],
      $paramsArrayToPassAsReference['parsedFiles'],
      'Testing $parsedFiles...'
    );
    self::assertEquals(
      [],
      $paramsArrayToPassAsReference['classesFromFile'],
      'Testing $classesFromFile...'
    );
    self::assertEquals(
      [],
      $paramsArrayToPassAsReference['parsedConstants'],
      'Testing $parsedConstants...'
    );
  }
}
