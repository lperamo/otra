<?php
/**
 * @author Lionel Péramo
 * @package otra\console\database
 */
declare(strict_types=1);
namespace otra\console\database\sqlMigrationExecute;

use DateTime;
use otra\bdd\Sql;
use otra\config\AllConfig;
use otra\console\database\Database;
use otra\OtraException;
use PDOStatement;
use const otra\cache\php\BASE_PATH;
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_INFO, CLI_INFO_HIGHLIGHT, END_COLOR, SUCCESS};
use const otra\cache\php\CORE_PATH;
use function otra\tools\files\returnLegiblePath;

const
  SQL_MIGRATION_GENERATE_ARG_VERSION = 2,
  SQL_MIGRATION_GENERATE_ARG_WAY = 3,
  SQL_MIGRATION_GENERATE_ARG_DATABASE_CONNECTION = 4,
  MIGRATIONS_FOLDER = BASE_PATH . 'migrations/',
  LABEL_THE_MIGRATION_FILE = 'The migration file ';

/**
 * Executes a single migration version up or down manually.
 *
 * @param array<int, string> $argumentsVector Command-line arguments, similar to those provided by $argv.
 *
 * @throws OtraException
 * @return void
 */
function sqlMigrationExecute(array $argumentsVector) : void
{
  // Checking migration way parameter
  $migrationWay = $argumentsVector[SQL_MIGRATION_GENERATE_ARG_WAY];

  if ($migrationWay !== 'up' && $migrationWay !== 'down')
  {
    echo CLI_ERROR, 'You must choose between ', CLI_INFO_HIGHLIGHT, 'up', CLI_ERROR, ' and ', CLI_INFO_HIGHLIGHT,
      'down', CLI_ERROR, '.', END_COLOR;
    throw new OtraException(code: 1, exit: true);
  }

  // Checking migration version file existence
  $migrationVersion = $argumentsVector[SQL_MIGRATION_GENERATE_ARG_VERSION];
  define(__NAMESPACE__ . '\\MIGRATION_FILE', MIGRATIONS_FOLDER . 'version' . $migrationVersion . '.php');
  require CORE_PATH . 'tools/files/returnLegiblePath.php';

  if (!file_exists(MIGRATION_FILE))
  {
    echo CLI_ERROR, LABEL_THE_MIGRATION_FILE, CLI_INFO_HIGHLIGHT, returnLegiblePath(MIGRATION_FILE), CLI_ERROR,
      ' does not exist.', END_COLOR;
    throw new OtraException(code: 1, exit: true);
  }

  // Checking database connection and then we initiate the connection
  $databaseConnection = $argumentsVector[SQL_MIGRATION_GENERATE_ARG_DATABASE_CONNECTION] ?? null;

  if (!Database::$init)
    Database::init($databaseConnection);

  $database = Sql::getDb($databaseConnection);
  $migration = require MIGRATION_FILE;

  // checking migration integrity
  if (!is_array($migration))
  {
    echo CLI_ERROR, LABEL_THE_MIGRATION_FILE, CLI_INFO_HIGHLIGHT, returnLegiblePath(MIGRATION_FILE), CLI_ERROR,
      ' does not return an array.', END_COLOR;
    throw new OtraException(code: 1, exit: true);
  }

  foreach (['version', $migrationWay] as $migrationKey)
  {
    if (!isset($migration[$migrationKey]))
    {
      echo CLI_ERROR, LABEL_THE_MIGRATION_FILE, CLI_INFO_HIGHLIGHT, returnLegiblePath(MIGRATION_FILE), CLI_ERROR,
        ' does not return the key ', CLI_INFO_HIGHLIGHT, $migrationKey, CLI_ERROR, '.', END_COLOR;
      throw new OtraException(code: 1, exit: true);
    }
  }

  echo CLI_BASE, 'Executing the migration file ', CLI_INFO_HIGHLIGHT, returnLegiblePath(MIGRATION_FILE), ' using ',
    CLI_INFO_HIGHLIGHT, $migrationWay, CLI_BASE, PHP_EOL;

  if (isset($migration['description']))
    echo CLI_INFO_HIGHLIGHT, '> ', CLI_BASE, $migration['description'], PHP_EOL;

  /**
   * @var PDOStatement $result
   */
  $queries = $migration[$migrationWay]();

  foreach (['transaction', 'queries'] as $queriesKey)
  {
    if (!isset($queries[$queriesKey]))
    {
      echo CLI_ERROR, 'The ', CLI_INFO_HIGHLIGHT, 'queries', CLI_ERROR, ' key in the migration file ',
        CLI_INFO_HIGHLIGHT, returnLegiblePath(MIGRATION_FILE), CLI_ERROR, ' does not return the key ',
        CLI_INFO_HIGHLIGHT, $queriesKey, CLI_ERROR, '.', END_COLOR;
      throw new OtraException(code: 1, exit: true);
    }
  }

  $executionTime = microtime(true);

  if ($queries['transaction'])
    $database->beginTransaction();

  $affectedRows = 0;
  $showableQueries = '';

  foreach($queries['queries'] as $query)
  {
    $result = $database->query($query);
    $affectedRows += $result->rowCount();
    $database->freeResult($result);
    $result->closeCursor();
    $showableQueries .= $query . PHP_EOL;
  }

  if ($queries['transaction'])
    $database->commit();

  $executionTime = round((microtime(true) - $executionTime) * 1000);

  echo CLI_INFO, $showableQueries, CLI_BASE, CLI_INFO_HIGHLIGHT, $affectedRows, CLI_BASE, ' row',
    ($affectedRows > 1
      ? 's have'
      : ' has'),
    ' been affected in ', CLI_INFO_HIGHLIGHT, $executionTime, CLI_BASE, ' ms.', SUCCESS;

  if ($migrationWay === 'up')
    $database->query(
      'INSERT INTO ' . AllConfig::$dbConnections[$database::$currentConnectionName]['db'] .
      '.otra_migration_versions(`version`, `executed_at`,`execution_time`) VALUES (' . $migrationVersion .
      ',' . (new DateTime())->getTimestamp() . ',' . $executionTime . ');');
}
