<?php
/**
 * @author Lionel Péramo
 * @package otra\console\database
 */
declare(strict_types=1);
namespace otra\console\database\sqlMigrationGenerate;

use otra\bdd\Sql;
use otra\console\database\Database;
use otra\OtraException;
use const otra\cache\php\{BASE_PATH, CORE_PATH};
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, END_COLOR, SUCCESS};
use function otra\tools\files\returnLegiblePath;

const
  SQL_MIGRATION_GENERATE_ARG_DATABASE_CONNECTION = 2,
  MIGRATIONS_FOLDER = BASE_PATH . 'migrations/';

/**
 * Creates a new blank migration file
 *
 * @throws OtraException
 * @return void
 */
function sqlMigrationGenerate(array $argumentsVector) : void
{
//  if (!property_exists(AllConfig::class, 'dbConnections'))
//  {
//    echo CLI_ERROR, 'No database connections are found!', END_COLOR, PHP_EOL;
//  }
  $databaseConnection = $argumentsVector[SQL_MIGRATION_GENERATE_ARG_DATABASE_CONNECTION] ?? null;

  if (!Database::$init)
    Database::init($databaseConnection);

  $database = Sql::getDb($databaseConnection);

  echo CLI_BASE, 'We generate the migrations table if it does not exist.', PHP_EOL;
  $queryCreateMigrationTable = $database->query(
    'CREATE TABLE IF NOT EXISTS otra_migration_versions(
      version INT UNSIGNED,
      executed_at BIGINT UNSIGNED,
      execution_time INT 
  )'
  );
  $database->freeResult($queryCreateMigrationTable);
  $queryGetLastVersion = $database->query(
    'SELECT version
    FROM otra_migration_versions
    ORDER BY version
    DESC LIMIT 1'
  );
  $lastVersion = $database->single($queryGetLastVersion);
  $database->freeResult($queryGetLastVersion);

  if ($lastVersion === false)
    $lastVersion = 0;

  // We check the existence of migrations folder, and we create it if needed
  if (!file_exists(MIGRATIONS_FOLDER))
  {
    if (!mkdir(MIGRATIONS_FOLDER, 0755, true))
    {
      echo CLI_ERROR, 'Cannot create the migrations folder!', END_COLOR, PHP_EOL;
      throw new OtraException(code: 1, exit: true);
    }

    echo 'Migrations folder created', CLI_SUCCESS, ' ✔', CLI_BASE, PHP_EOL;
  }

  define (__NAMESPACE__ . '\\NEW_VERSION_FILE', MIGRATIONS_FOLDER . 'version' . $lastVersion . '.php');

  require CORE_PATH . 'tools/files/returnLegiblePath.php';

  if (file_exists(NEW_VERSION_FILE))
  {
    echo CLI_ERROR . 'The new migration file ', CLI_INFO_HIGHLIGHT, returnLegiblePath(NEW_VERSION_FILE), CLI_ERROR,
    ' cannot be created as it already exists!', PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }

  file_put_contents(
    NEW_VERSION_FILE,
    <<<CODE
<?php
declare(strict_types=1);
namespace otra\migrations;

return [
  'version' => $lastVersion,
  'description' => '',
  'up' => function()
  {
    // this up() migration is auto-generated, please modify it to your needs
  },
  'down' => function()
  {
    // this down() migration is auto-generated, please modify it to your needs
  }
];
CODE
 );

  echo 'The new blank migration file ', CLI_INFO_HIGHLIGHT, returnLegiblePath(NEW_VERSION_FILE), CLI_BASE, ' has been generated', SUCCESS;
}
