<?php
declare(strict_types=1);

namespace src\console\deployment\taskFileOperation;

use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\cache\php\{CONSOLE_PATH, CORE_PATH, TEST_PATH};
use function otra\console\deployment\genBootstrap\compressPHPFile;
use function otra\tools\copyFileAndFolders;

/**
 * @runTestsInSeparateProcesses
 */
class CompressTest extends TestCase
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
    require CORE_PATH . 'tools/copyFilesAndFolders.php';
    copyFileAndFolders(
      [TEST_PATH . 'examples/deployment/BackupFileToCompress.php'],
      [self::FILE_TO_COMPRESS]
    );
  }

  protected function tearDown(): void
  {
    parent::tearDown();

    // cleaning
    unlink(self::FINAL_COMPRESSED_FILE);
  }

  /**
   * @author Lionel Péramo
   */
  public function testCompressPHPFile()
  {
    // launching
    compressPHPFile(self::FILE_TO_COMPRESS, self::COMPRESSED_FILE);

    // testing
    static::assertFileEquals(
      self::BACKUP_COMPRESSED_FILE,
      self::FINAL_COMPRESSED_FILE,
      'Testing expected ' . self::BACKUP_COMPRESSED_FILE . ' vs ' . self::FINAL_COMPRESSED_FILE
    );
  }
}
