<?php
declare(strict_types=1);

namespace src\console\database\sqlExecute;

use otra\console\database\Database;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use const otra\cache\php\{CONSOLE_PATH, TEST_PATH};
use function otra\console\database\sqlExecute\sqlExecute;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class SqlExecuteTaskTest extends TestCase
{
  private const
    OTRA_BINARY = 'otra.php',
    OTRA_TASK_SQL_EXECUTE = 'sqlExecute',
    OTRA_VARIABLE_DATABASE_SCHEMA_FILE = 'schemaFile',
    CONFIG_FOLDER = TEST_PATH . 'src/bundles/HelloWorld/config/data/',
    CONFIG_FOLDER_YML = self::CONFIG_FOLDER . 'yml/',
    SCHEMA_FILE = 'schema.yml',
    SCHEMA_ABSOLUTE_PATH = self::CONFIG_FOLDER_YML . self::SCHEMA_FILE;

  /**
   * @throws OtraException
   * @throws ReflectionException
   */
  public function testExecuteFile_DoesNotExist() : void
  {
    (new ReflectionClass(Database::class))->getProperty(self::OTRA_VARIABLE_DATABASE_SCHEMA_FILE)
      ->setValue(self::SCHEMA_ABSOLUTE_PATH);

    $this->expectException(OtraException::class);
    $this->expectExceptionMessage('The file "blabla" does not exist !');

    require CONSOLE_PATH . 'database/sqlExecute/sqlExecuteTask.php';
    sqlExecute([self::OTRA_BINARY, self::OTRA_TASK_SQL_EXECUTE, 'blabla']);
  }

  /**
   * @doesNotPerformAssertions
   */
  public function testExecuteFile_Exists() : void
  {
    echo __DIR__;
    //    Database::executeFile();
  }
}
