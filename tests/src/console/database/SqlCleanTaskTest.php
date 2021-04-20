<?php
declare(strict_types=1);

namespace src\console\database;

use otra\bdd\Sql;
use otra\console\Database;
use otra\console\TasksManager;
use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class SqlCleanTaskTest extends TestCase
{
  private const
    OTRA_TASK_SQL_CLEAN = 'sqlClean',
    OTRA_TASK_HELP = 'help',
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
   * @throws \otra\OtraException
   * @throws \ReflectionException
   * @author Lionel Péramo
   */
  public function testSqlClean() : void
  {
    // context
    $_SERVER[APP_ENV] = 'prod';
    removeFieldScopeProtection(Database::class, 'boolSchema')->setValue(false);
    removeFieldScopeProtection(Database::class, 'folder')->setValue('tests/src/bundles/');

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
    $sqlPath = removeFieldScopeProtection(Database::class, 'pathSql')->getValue();
    self::assertEquals([], glob($sqlPath . '/*.sql'));
    self::assertEquals([], glob($sqlPath . 'truncate/*.sql'));
    self::expectOutputString(CLI_BASE . 'Cleaning done' . CLI_SUCCESS . ' ✔' . END_COLOR . PHP_EOL);

    // cleaning
    require CORE_PATH . 'tools/cleanFilesAndFolders.php';

    cleanFileAndFolders([
      self::CONFIG_FOLDER_SQL,
      self::CONFIG_FOLDER_YML
    ]);

    require_once(self::TEST_CONFIG_GOOD_PATH);

    Sql::getDb(null, false);
    Sql::$instance->query('DROP DATABASE IF EXISTS `' . self::DATABASE_NAME . '`;');
  }

  public function testSqlCleanHelp()
  {
    $this->expectOutputString(
      CLI_BASE .
      str_pad(self::OTRA_TASK_SQL_CLEAN, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Removes sql and yml files in the case where there are problems that had corrupted files.' .
      PHP_EOL . CLI_INFO_HIGHLIGHT .
      '   + ' . str_pad('cleaningLevel', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'Type 1 in order to also remove the file that describes the tables order.' . PHP_EOL . END_COLOR
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_SQL_CLEAN]
    );
  }
}
