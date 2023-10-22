<?php
declare(strict_types=1);

namespace src\console\database\sqlClean;

use otra\bdd\Sql;
use otra\console\database\Database;
use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\cache\php\{APP_ENV,CORE_PATH,PROD,TEST_PATH};
use const otra\console\{CLI_BASE, CLI_SUCCESS, END_COLOR};
use function otra\tools\{cleanFileAndFolders, copyFileAndFolders};

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class SqlCleanTaskTest extends TestCase
{
  private const
    OTRA_TASK_SQL_CLEAN = 'sqlClean',
    DEPLOY_ARG_SQL_CLEANING_LEVEL = 2,
    TEST_CONFIG_GOOD_PATH = TEST_PATH . 'config/AllConfigGood.php',
    DATABASE_NAME = 'testDB',
    CONFIG_FOLDER = TEST_PATH . 'src/bundles/HelloWorld/config/data/',
    CONFIG_BACKUP_FOLDER = TEST_PATH . 'config/data/',
    CONFIG_FOLDER_SQL = self::CONFIG_FOLDER . 'sql/',
    CONFIG_FOLDER_SQL_BACKUP = self::CONFIG_BACKUP_FOLDER . 'sqlBackup/',
    CONFIG_FOLDER_YML = self::CONFIG_FOLDER . 'yml/',
    CONFIG_FOLDER_YML_BACKUP = self::CONFIG_BACKUP_FOLDER . 'ymlBackup/';

  /**
   * @throws OtraException
   * @throws ReflectionException
   * @author Lionel Péramo
   */
  public function testSqlClean() : void
  {
    // context
    $_SERVER[APP_ENV] = PROD;
    $reflectedClass = (new ReflectionClass(Database::class));
    $reflectedClass->getProperty('folder')->setValue('tests/src/bundles/');

    require CORE_PATH . 'tools/copyFilesAndFolders.php';
    copyFileAndFolders(
      [
        self::CONFIG_FOLDER_YML_BACKUP,
        self::CONFIG_FOLDER_SQL_BACKUP
      ],
      [
        self::CONFIG_FOLDER_YML,
        self::CONFIG_FOLDER_SQL
      ]
    );

    // launching
    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_SQL_CLEAN,
      ['otra.php', self::OTRA_TASK_SQL_CLEAN, self::DEPLOY_ARG_SQL_CLEANING_LEVEL]
    );

    // testing
    $sqlPath = (new ReflectionClass(Database::class))->getProperty('pathSql')->getValue();
    self::assertSame([], glob($sqlPath . '/*.sql'));
    self::assertSame([], glob($sqlPath . 'truncate/*.sql'));
    self::expectOutputString(CLI_BASE . 'Cleaning done' . CLI_SUCCESS . ' ✔' . END_COLOR . PHP_EOL);

    // cleaning
    require CORE_PATH . 'tools/cleanFilesAndFolders.php';

    cleanFileAndFolders([
      self::CONFIG_FOLDER_SQL,
      self::CONFIG_FOLDER_YML
    ]);

    require_once self::TEST_CONFIG_GOOD_PATH;

    Sql::getDb(null, false);
    Sql::$instance->query('DROP DATABASE IF EXISTS `' . self::DATABASE_NAME . '`;');
  }
}
