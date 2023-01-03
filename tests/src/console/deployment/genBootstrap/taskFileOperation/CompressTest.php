<?php
declare(strict_types=1);

namespace src\console\deployment\genBootstrap\taskFileOperation;

use otra\OtraException;
use phpunit\framework\TestCase;
use function otra\tools\files\compressPHPFile;
use const otra\cache\php\{CONSOLE_PATH, CORE_PATH, TEST_PATH};
use function otra\tools\copyFileAndFolders;

/**
 * @runTestsInSeparateProcesses
 */
class CompressTest extends TestCase
{
  private const
    FILE_TO_COMPRESS = TEST_PATH . 'examples/deployment/FileToCompress.php',
    COMPRESSED_FILE = TEST_PATH . 'examples/deployment/CompressedFile.php',
    BACKUP_COMPRESSED_FILE = TEST_PATH . 'examples/deployment/BackupCompressedFile.php';

  // It fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  /**
   * @throws OtraException
   */
  protected function setUp(): void
  {
    parent::setUp();
    require CORE_PATH . 'tools/files/compressPhpFile.php';
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
    unlink(self::COMPRESSED_FILE);
  }

  /**
   * @author Lionel Péramo
   */
  public function testCompressPHPFile(): void
  {
    // launching
    compressPHPFile(self::FILE_TO_COMPRESS, self::COMPRESSED_FILE);

    // testing
    static::assertFileEquals(
      self::BACKUP_COMPRESSED_FILE,
      self::COMPRESSED_FILE,
      'Testing expected ' . self::BACKUP_COMPRESSED_FILE . ' vs ' . self::COMPRESSED_FILE
    );
  }
}
