<?php
/**
 * @author Lionel Péramo
 * @package otra\console\database
 */
declare(strict_types=1);
namespace otra\console\database\sqlImportFixtures;

use Exception;
use otra\console\database\Database;
use otra\OtraException;
use otra\Session;
use ReflectionException;
use Symfony\Component\Yaml\Yaml;
use const otra\console\{CLI_BASE, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, CLI_WARNING, END_COLOR};

const
  SQL_IMPORT_FIXTURES_ARG_DATABASE_NAME = 2,
  SQL_IMPORT_FIXTURES_ARG_CONFIGURATION = 3;

/**
 * Creates the database fixtures from a database.
 *
 * @param array<int, string> $argumentsVector Command-line arguments, similar to those provided by $argv.
 *
 * @throws ReflectionException
 * @throws OtraException
 * @return void
 */
function sqlImportFixtures(array $argumentsVector): void
{
  Session::init();

  if (isset($argumentsVector[SQL_IMPORT_FIXTURES_ARG_DATABASE_NAME]))
  {
    $databaseName = $argumentsVector[SQL_IMPORT_FIXTURES_ARG_DATABASE_NAME];
    $confToUse = $argumentsVector[SQL_IMPORT_FIXTURES_ARG_CONFIGURATION] ?? null;
  }
  else
  {
    $databaseName = null;
    $confToUse = null;
  }

  if (!Database::$init)
    Database::init();

  $database = Database::_initImports($databaseName, $confToUse);

  if (!file_exists(Database::$tablesOrderFile))
  {
    echo CLI_WARNING, 'You must create the tables order file (', Database::$tablesOrderFile,
    ') before using this task !', END_COLOR;
    exit(1);
  }

  // Everything is in order, we can clean the old files before the process
  array_map('unlink', glob(Database::$pathYmlFixtures . '*.yml'));

  // We ensure us that the folder where we have to create the fixtures file exists
  if (!file_exists(Database::$pathYmlFixtures))
  {
    $exceptionMessage = Database::ERROR_CANNOT_REMOVE_THE_FOLDER_SLASH . Database::$pathYmlFixtures . '\'.';

    try
    {
      if (!mkdir(Database::$pathYmlFixtures, 0777, true))
        throw new OtraException($exceptionMessage, E_CORE_ERROR);
    } catch(Exception $exception)
    {
      throw new OtraException(
        Database::ERROR_CLOSE_DIR_FORGOT_CALL .
        $exceptionMessage, $exception->getCode()
      );
    }
  }

  /** REAL BEGINNING OF THE TASK */
  $tablesOrder = Yaml::parse(file_get_contents(Database::$tablesOrderFile));

  $foreignKeysMemory = [];

  /** @var string $table */
  foreach ($tablesOrder as $table)
  {
    $content = $table . ':' . PHP_EOL;
    /** @var array<int,array<string, string>> $columns */
    $columns = $database->values($database->query(
      'SELECT COLUMN_KEY, COLUMN_NAME, DATA_TYPE
        FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = \'' . $databaseName .
      '\' AND TABLE_NAME = \'' . $table . '\'
        ORDER BY ORDINAL_POSITION'
    ));

    // If there are columns ...
    if (0 < count($columns))
    {
      $importFixturesSql = 'SELECT ';

      foreach ($columns as $column)
      {
        $importFixturesSql .= '`' . $column['COLUMN_NAME'] . '`, ';
      }

      // the substr removes the last comma
      /** @var array<int, array<string, string>> $tableRows */
      $tableRows = $database->values(
        $database->query(substr($importFixturesSql, 0, -2) . ' FROM ' . $databaseName . '.' . $table)
      );

      echo substr($importFixturesSql, 0, -2) . ' FROM ' . $databaseName . '.' . $table, PHP_EOL;

      // If we have results, there is a real interest to create the fixtures (Why create empty files ?!)
      if (0 < count($tableRows))
      {
        $foreignKeysMemory[$table] = [];

        /** @var array<int, array<string, string>> $constraints */
        $constraints = $database->values($database->query(
          'SELECT REFERENCED_TABLE_NAME, COLUMN_NAME, REFERENCED_COLUMN_NAME, CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = \'' . $databaseName . '\'
            AND TABLE_NAME = \'' . $table . '\'
            AND CONSTRAINT_NAME <> \'PRIMARY\'
            AND REFERENCED_TABLE_NAME IS NOT NULL' // to prevent retrieving indexes
        ));

        $foreignConstraintsCount = count($constraints);

        foreach ($tableRows as $keyRow => $tableRow)
        {
          $fixtureId = $table . '_' . $keyRow;
          $content .= '  ' . $fixtureId . ':' . PHP_EOL;

          foreach ($tableRow as $keyCol => $colOfRow)
          {
            $content .= '    ';
            // We check if the column has a foreign key assigned or not
            if (0 < $foreignConstraintsCount)
            {
              $arrayKeyFromConstraints = array_search($keyCol, array_column($constraints, 'COLUMN_NAME'));

              if (null !== $colOfRow && $keyCol === $constraints[$arrayKeyFromConstraints]['COLUMN_NAME'])
              {
                $content .= $constraints[$arrayKeyFromConstraints]['REFERENCED_TABLE_NAME'] . ': ' .
                  $foreignKeysMemory[$constraints[$arrayKeyFromConstraints]['REFERENCED_TABLE_NAME']][(int)$colOfRow] .
                  PHP_EOL;
                continue;
              }
            }

            $columnMetaData = $columns[array_search($keyCol, array_column($columns, 'COLUMN_NAME'))];
            /** We check if the column is a primary key and, if it's the case, we put the name of the actual table,
             * and we store the association for later to manage the foreign key associations */
            if ('PRI' === $columnMetaData['COLUMN_KEY'])
            {
              $foreignKeysMemory[$table][$colOfRow] = $fixtureId;
              $content .= $keyCol;
            }
            else // if it's a classic column...
              $content .= $keyCol;

            $content .= ': ';

            if (null === $colOfRow)
              $content .= '~';
            elseif (is_string($colOfRow))
            {
              // For some obscure reasons, PHP_EOL cannot work in this case as it is always returning \n in my tests...
              if (!str_contains($colOfRow, "\n"))
              {
                $quoteIfString = in_array(
                  $columnMetaData['DATA_TYPE'],
                  ['char', 'varchar', 'text', 'blob', 'timestamp'],
                  true
                ) ? "'" : '';
                $content .= $quoteIfString . str_replace('\'', '\'\'', $colOfRow) . $quoteIfString;
              }
              else // Multi lines text management
                $content .= '|' . PHP_EOL . '      ' . str_replace("\n", "\n      ", $colOfRow);
            }
            else
              $content .= $colOfRow;

            $content .= PHP_EOL;
          }
        }
      }
    }

    // We can now create the fixture file...
    file_put_contents(Database::$pathYmlFixtures . $table . '.yml', $content);

    echo CLI_BASE, 'File ', CLI_INFO_HIGHLIGHT, $table . '.yml', CLI_BASE, ' created', CLI_SUCCESS, ' ✔', END_COLOR,
    PHP_EOL;
  }
}
