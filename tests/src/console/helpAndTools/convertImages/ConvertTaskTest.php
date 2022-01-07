<?php
declare(strict_types=1);

namespace src\console\helpAndTools\convertImages;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;

use function otra\tools\delTree;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\{BASE_PATH, CORE_PATH, TEST_PATH};
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT};

/**
 * @runTestsInSeparateProcesses
 */
class ConvertTaskTest extends TestCase
{
  private const
    OTRA_TASK_CONVERT_IMAGES = 'convertImages',
    CONVERT_PATH = BASE_PATH . 'web/testsConvert/',
    TEST_IMAGE_BASENAME = 'dummy',
    TEST_IMAGE_EXTENSION = 'png',
    TEST_IMAGE_DEST_EXTENSION = 'webp',
    TEST_IMAGE_FULL_NAME = self::TEST_IMAGE_BASENAME . '.' . self::TEST_IMAGE_EXTENSION,
    TEST_IMAGE_SOURCE_PATH = self::CONVERT_PATH . self::TEST_IMAGE_FULL_NAME,
    TEST_IMAGE_DEST_PATH = self::CONVERT_PATH . self::TEST_IMAGE_BASENAME . '.' . self::TEST_IMAGE_DEST_EXTENSION;

  /**
   * @throws OtraException
   */
  public static function tearDownAfterClass(): void
  {
    parent::tearDownAfterClass();
    // cleaning
    if (file_exists(self::TEST_IMAGE_DEST_PATH) && !unlink(self::TEST_IMAGE_DEST_PATH))
      throw new OtraException('Cannot unlink ' . self::TEST_IMAGE_DEST_PATH);

    require CORE_PATH . 'tools/deleteTree.php';

    if (file_exists(self::CONVERT_PATH))
      delTree(self::CONVERT_PATH);
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function test() : void
  {
    // context
    mkdir(self::CONVERT_PATH);
    copy(
    TEST_PATH . 'examples/images/' . self::TEST_IMAGE_FULL_NAME,
      self::TEST_IMAGE_SOURCE_PATH
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_CONVERT_IMAGES,
      [
        'otra.php',
        self::OTRA_TASK_CONVERT_IMAGES,
        self::TEST_IMAGE_EXTENSION,
        self::TEST_IMAGE_DEST_EXTENSION,
        '75',
        'false'
      ]
    );

    // testing
    self::assertFileDoesNotExist(
      self::TEST_IMAGE_SOURCE_PATH,
      'Testing if ' . CLI_INFO_HIGHLIGHT . self::TEST_IMAGE_SOURCE_PATH . CLI_ERROR . ' does not exist'
    );

    self::assertFileExists(
      self::TEST_IMAGE_DEST_PATH,
      'Testing if ' . CLI_INFO_HIGHLIGHT . self::TEST_IMAGE_DEST_PATH . CLI_ERROR . ' exists'
    );
  }
}
