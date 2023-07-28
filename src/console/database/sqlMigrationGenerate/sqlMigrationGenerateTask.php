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
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_SUCCESS, END_COLOR, SUCCESS};
use function otra\tools\files\returnLegiblePath;

const
  SQL_MIGRATION_GENERATE_ARG_DATABASE_CONNECTION = 2,
  MIGRATIONS_FOLDER = BASE_PATH . 'migrations/',
  MIGRATION_VERSION_LABEL_PART_LENGTH = 7, // 'version' from the left
  MIGRATION_EXTENSION_LABEL_PART_LENGTH = -4; // '.php' from the right

/**
 * Creates a new blank migration file
 *
 * @param array<int, string> $argumentsVector Command-line arguments, similar to those provided by $argv.
 *
 * @throws OtraException
 * @return void
 */
function sqlMigrationGenerate(array $argumentsVector) : void
{
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

  require CORE_PATH . 'tools/files/returnLegiblePath.php';

  // We check the existence of migrations folder, and we create it if needed
  if (!file_exists(MIGRATIONS_FOLDER))
  {
    if (!mkdir(MIGRATIONS_FOLDER, 0755, true))
    {
      echo CLI_ERROR, 'Cannot create the migrations folder!', END_COLOR, PHP_EOL;
      throw new OtraException(code: 1, exit: true);
    }

    echo 'Migrations folder ', returnLegiblePath(MIGRATIONS_FOLDER), ' created', CLI_SUCCESS, ' ✔', CLI_BASE,
      PHP_EOL;
    $newVersion = 0;
  } else
  {
    $files = scandir(MIGRATIONS_FOLDER);

    if (count($files) > 2) // 2 to skip `.` and `..` folders
    {
      $lastFile = array_pop($files);
      $newVersion = (int) substr(
        $lastFile,
        MIGRATION_VERSION_LABEL_PART_LENGTH,
        MIGRATION_EXTENSION_LABEL_PART_LENGTH
      ) + 1;
    }
    else
      $newVersion = 0;

    unset($files);
  }

  define (__NAMESPACE__ . '\\NEW_VERSION_FILE', MIGRATIONS_FOLDER . 'version' . $newVersion . '.php');
  file_put_contents(
    NEW_VERSION_FILE,
    <<<CODE
<?php
declare(strict_types=1);
namespace otra\migrations;

return [
  'version' => $newVersion,
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

  echo 'The new blank migration file ', returnLegiblePath(NEW_VERSION_FILE), CLI_BASE, ' has been generated', SUCCESS;
}
