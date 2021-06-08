<?php
declare(strict_types=1);

namespace src\console\helpAndTools\hash;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\bin\TASK_CLASS_MAP_PATH;

/**
 * @runTestsInSeparateProcesses
 */
class HashTaskTest extends TestCase
{
  private const
    OTRA_TASK_HASH = 'hash',
    BLOWFISH_SALT_LENGTH = 22;

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testHash() : void
  {
    // testing
    $this->expectOutputRegex('@\$2y\$03\$[a-zA-Z0-9]{' . self::BLOWFISH_SALT_LENGTH . '}@');

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HASH,
      ['otra.php', self::OTRA_TASK_HASH, 3]
    );
  }
}
