<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\database
 */
declare(strict_types=1);

namespace otra\console\database\sqlCreateFixtures;

use Exception;
use otra\bdd\Sql;
use otra\console\database\Database;
use otra\OtraException;
use PDO;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use function otra\console\database\sqlExecute\sqlExecute;
use const otra\cache\php\
{BASE_PATH, CONSOLE_PATH, CORE_PATH, DIR_SEPARATOR};
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_INFO, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, CLI_WARNING, END_COLOR};
use function otra\src\tools\debug\validateYaml;

const
  OTRA_BINARY = 'otra.php',
  OTRA_TASK_SQL_EXECUTE = 'sqlExecute',
  SQL_CREATE_FIXTURES_ARG_DATABASE_NAME = 2,
  SQL_CREATE_FIXTURES_ARG_MASK = 3;

/**
 * Executes the sql file for the specified table and database
 *
 * @param string $databaseName The database name
 * @param string $table        The table name
 *
 * @throws OtraException
 */
function executeFixture(string $databaseName, string $table) : void
{
  // the 'once' version as we can have to execute multiple fixtures file in a row
  require_once CONSOLE_PATH . 'database/sqlExecute/sqlExecuteTask.php';
  sqlExecute(
    [
      OTRA_BINARY,
      OTRA_TASK_SQL_EXECUTE,
      Database::$pathSqlFixtures . $databaseName . '_' . $table . '.sql',
      $databaseName
    ]
  );
  echo CLI_SUCCESS, '[SQL EXECUTION]', END_COLOR, PHP_EOL;
}

/**
 * Analyze the fixtures contained in the file and return the found table names
 *
 * @param string $file Fixture file name to analyze
 *
 * @return array The found table names
 */
function analyzeFixtures(string $file) : array
{
  // Gets the fixture data
  try
  {
    $fixturesData = Yaml::parse(file_get_contents($file));
  } catch(ParseException $parseException)
  {
    echo CLI_ERROR, $parseException->getMessage(), END_COLOR, PHP_EOL;
    exit(1);
  }

  $tablesToCreate = [];

  // For each table
  foreach (array_keys($fixturesData) as $table)
  {
    $tablesToCreate[$table] = $file;
  }

  return $tablesToCreate;
}

/**
 * Create the sql content of the wanted fixture. We only allow one table per file for simplicity and performance.
 *
 * @param string              $databaseName   The database name to use
 * @param string              $table          The table name relating to the fixture to create
 * @param array<string,array> $fixturesData   The table fixtures
 * @param array<string,array> $tableData      The table data form the database schema in order to have the properties type
 * @param string[]            $sortedTables   Final sorted tables array
 * @param array               $fixturesMemory An array that stores foreign identifiers in order to resolve yaml aliases
 * @param string              $createdFile    Name of the fixture file that will be created.
 *
 * @throws OtraException If a database relation is missing or if we can't create the fixtures folder
 */
function createFixture(
  string $databaseName,
  string $table,
  array $fixturesData,
  array $tableData,
  array $sortedTables,
  array &$fixturesMemory,
  string $createdFile
) : void
{
  if (!Database::$init)
    Database::init();

  $first = true;
  $ymlIdentifiers = $table . ': ' . PHP_EOL;
  $tableSql = /** @lang text Necessary to avoid false positives from PHPStorm inspections */
    'USE ' . $databaseName . ';' . PHP_EOL . 'SET NAMES utf8mb4;' . PHP_EOL . PHP_EOL . 'INSERT INTO `' . $table . '` (';
//    $localMemory This variable stores the identifiers found in this table that are not available in
//    sorted tables ?
  $localMemory = $values = $properties = [];
  $theProperties = '';

  $databaseId = 1; // The database ids begin to 1 by default

  /** IMPORTANT : The Yml identifiers are, in fact, not real ids in the database sense, but rather a temporary id that
   * represents the position of the line in the database ! */
  foreach (array_keys($fixturesData) as $fixtureName)
  {
    $ymlIdentifiers .= '  ' . $fixtureName . ': ' . $databaseId++ . PHP_EOL;
  }

  unset($fixtureName);

  $fixtureFolder = Database::$pathYmlFixtures . Database::$fixturesFileIdentifiers . DIR_SEPARATOR;

  // if the fixtures' folder doesn't exist, we create it.
  if (!file_exists($fixtureFolder))
  {
    $exceptionMessage = Database::ERROR_CANNOT_REMOVE_THE_FOLDER_SLASH . $fixtureFolder . '\'.';

    try
    {
      if (!mkdir($fixtureFolder, 0777, true))
        throw new OtraException($exceptionMessage, E_CORE_ERROR);
    } catch(Exception $exception)
    {
      throw new OtraException(Database::ERROR_CLOSE_DIR_FORGOT_CALL . $exceptionMessage, $exception->getCode());
    }
  }

  file_put_contents($fixtureFolder . $databaseName . '_' . $table . '.yml', $ymlIdentifiers);

  echo 'Data  ', CLI_SUCCESS, '[YML IDENTIFIERS] ', END_COLOR;

  /**
   * If this table has relations, we store all the data from the related tables in $fixtureMemory array.
   */
  if (isset($tableData['relations']))
  {
    foreach (array_keys($tableData['relations']) as $relation)
    {
      try
      {
        /** @var array<string, array<string, int>> $fixturesRows */
        $fixturesRows = Yaml::parse(
          file_get_contents($fixtureFolder . $databaseName . '_' . ((string) $relation) . '.yml')
        );
      } catch(ParseException $parseException)
      {
        echo CLI_ERROR, $parseException->getMessage(), END_COLOR, PHP_EOL;
        throw new OtraException(code: 1, exit: true);
      }

      foreach ($fixturesRows as $otherTable => $otherTableData)
      {
        $fixturesMemory[$otherTable] = $otherTableData;
      }
    }
  }

  Sql::getDb();

  // We ensure us that we use the correct database
  /** @var PDO $dbConfig 'query' method returns PDOStatement but to avoid a PHPStorm warning we said PDO! */
  $dbConfig = Sql::$instance->query('USE ' . $databaseName);
  Sql::$instance->freeResult($dbConfig);

  // We ensure us that we can make multiple queries in one statement
  try {
    $dbConfig->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);
  } catch(Exception $exception)
  {
    // Then the driver does not handle this attribute
  }

  /**
   * THE REAL, COMPLICATED, WORK BEGINS HERE.
   */
  $databaseId = 1; // The database ids begin to 1 by default

  foreach ($fixturesData as $fixtureName => $properties)
  {
    $localMemory[$fixtureName] = $databaseId;
    $databaseId++;
    // $theValues the current tuple
    $theValues = '';

    foreach ($properties as $property => $value)
    {
      $propertyRefersToAnotherTable = in_array($property, $sortedTables);

      if ($propertyRefersToAnotherTable && !isset($tableData['relations'][$property]))
        throw new OtraException(
          'Either it lacks a relation to the table `' . $table . '` for a `' . $property .
          '` like property or you have put this property name by error in file `' . $table . '.yml.',
          E_CORE_ERROR
        );

      // If the property refers to another table, then we search the corresponding foreign key name
      // (e.g. : lpcms_module -> 'module1' => fk_id_module -> 4 )
      $theProperties .= '`' .
        ($propertyRefersToAnotherTable
          ? $tableData['relations'][$property]['local']
          : $property) .
        '`, ';

      $properties [] = $property;

      if (!$propertyRefersToAnotherTable)
      {
        if (is_bool($value))
          $value = $value ? 1 : 0;
        elseif (is_string($value) && 'int' == $tableData['columns'][$property]['type'])
          $value = $localMemory[$value];

        $theValues .= (null === $value)
          ? 'NULL, '
          : (is_string($value)
            ? '\'' . addslashes($value) . '\', '
            : $value . ', ');
      } else // If it is a foreign key
      {
        // We retrieve the id in the database thanks to the known position of the row that we have stored before
        $dbConfig = Sql::$instance->query('SET @rownum=0');
        Sql::$instance->freeResult($dbConfig);

        $dbConfig = Sql::$instance->query(
          'SELECT
                x.' . $tableData['relations'][$property]['foreign'] .'
            FROM
            (SELECT
                    t.' . $tableData['relations'][$property]['foreign']. ', @rownum:=@rownum + 1 AS position
                FROM
                    ' . $property . ' t
            ) x
            WHERE
                x.position = ' . $fixturesMemory[$property][$value]
        );
        $foreignIdValue = Sql::$instance->single($dbConfig);
        Sql::$instance->freeResult($dbConfig);

        $theValues .= $foreignIdValue . ', ';
      }

      $values [] = [$fixtureName => $value];
    }

    if ($first)
      $tableSql .= substr($theProperties, 0, -2) . ') VALUES';

    $tableSql .= '(' . substr($theValues, 0, -2) . '),';
    $first = false;
  }

  $tableSql = substr($tableSql, 0, -1) . ';' . PHP_EOL;

  // We create sql file that can generate the fixtures in the BDD.
  if (!file_exists(Database::$pathSqlFixtures))
    mkdir(Database::$pathSqlFixtures, 0777, true);

  file_put_contents($createdFile, $tableSql);

  echo CLI_SUCCESS, '[SQL CREATION] ', END_COLOR;
}

/**
 * Creates all the sql fixtures files for the specified database and executes them.
 *
 * @param array $argumentsVector
 *
 * @throws OtraException
 *  If we cannot open the YAML fixtures folder
 *  If there is no YAML schema
 *  If the file that describe the table priority/order doesn't exist
 *  If we cannot create fixtures sql folder
 *  If we attempt to create an already existing file
 * @return void
 */
function sqlCreateFixtures(array $argumentsVector) : void
{
  $databaseName = $argumentsVector[SQL_CREATE_FIXTURES_ARG_DATABASE_NAME];
  /**
   * 1 => we truncate the table before inserting the fixtures,
   * 2 => we clean the fixtures sql files, and THEN we truncate the table before inserting the fixtures
   */
  $mask = isset($argumentsVector[SQL_CREATE_FIXTURES_ARG_MASK])
    ? (int)$argumentsVector[SQL_CREATE_FIXTURES_ARG_MASK]
    : 0;

  if (!Database::$init)
    Database::init();

  // Analyzes the database schema in order to guess the properties types
  if (!file_exists(Database::$schemaFile))
    throw new OtraException(
      'You have to create a database schema file in ' . CLI_INFO_HIGHLIGHT . 'config/data/schema.yml' .
      CLI_BASE . ' before using fixtures. Searching for : ' . CLI_INFO_HIGHLIGHT . Database::$schemaFile,
      E_NOTICE
    );

  // Looks for the fixtures file
  if (!file_exists(Database::$pathYmlFixtures) && !mkdir(Database::$pathYmlFixtures))
  {
    require CORE_PATH . 'tools/debug/returnLegiblePath.php';
    echo CLI_ERROR, 'Cannot create the fixtures folder ', CLI_INFO_HIGHLIGHT, Database::$pathYmlFixtures, CLI_ERROR, '.',
    PHP_EOL, END_COLOR;
    throw new OtraException(code: 1, exit: true);
  }

  if (false === ($folder = opendir(Database::$pathYmlFixtures)))
  {
    closedir($folder);
    throw new OtraException(
      'Cannot open the YAML fixtures folder ' . Database::$pathYmlFixtures . ' !',
      E_CORE_ERROR
    );
  }

  if (!file_exists(Database::$tablesOrderFile))
    throw new OtraException(
      'You must use the database generation task before using the fixtures (no ' .
      substr(Database::$tablesOrderFile, strlen(BASE_PATH)) . ' file)',
      E_CORE_WARNING
    );

  if (!file_exists(Database::$pathSqlFixtures) && !mkdir(Database::$pathSqlFixtures, 0777, true))
    throw new OtraException(Database::ERROR_CANNOT_CREATE_THE_FOLDER . Database::$pathSqlFixtures . ' !', E_CORE_ERROR);

  require CORE_PATH . 'tools/debug/validateYaml.php';
  $schema = validateYaml(file_get_contents(Database::$schemaFile), Database::$schemaFile);
  $tablesOrder = Yaml::parse(file_get_contents(Database::$tablesOrderFile));
  $fixtureFileNameBeginning = Database::$pathSqlFixtures . $databaseName . '_';

  // We clean the fixtures sql files whether it's needed
  if (2 === $mask)
  {
    array_map('unlink', glob($fixtureFileNameBeginning . '*.sql'));
    echo CLI_BASE, 'Fixtures sql files cleaned', CLI_SUCCESS, ' ✔', END_COLOR, PHP_EOL;
  }

  $tablesToCreate = [];

  // Browse all the fixtures files
  while (false !== ($handle = readdir($folder)))
  {
    if ('.' !== $handle && '..' !== $handle && '' !== $handle && 'ids' !== $handle)
    {
      $handle = Database::$pathYmlFixtures . $handle;

      // If it's not a folder (for later if we want to add some "complex" folder management ^^)
      if (!is_dir($handle))
      {
        $tables = analyzeFixtures($handle);

        // Beautify the array
        foreach ($tables as $table => &$handle)
        {
          $tablesToCreate[$databaseName][$table] = $handle;
        }
      }
    }
  }

  closedir($folder);

  $color = 0;
  $fixturesMemory = [];
  $weNeedToTruncate = 0 < $mask;
  $truncatePath = Database::$pathSql . 'truncate';

  if ($weNeedToTruncate && !file_exists($truncatePath) && !mkdir($truncatePath))
    throw new OtraException(Database::ERROR_CANNOT_CREATE_THE_FOLDER . $truncatePath);

  foreach ($tablesOrder as $table)
  {
    echo PHP_EOL, $color % 2 ? CLI_INFO : CLI_INFO_HIGHLIGHT;

    // We truncate the tables
    if (true === $weNeedToTruncate)
      Database::truncateTable($databaseName, $table);

    for ($tableIndex = 0, $cptTables = count($tablesToCreate[$databaseName]); $tableIndex < $cptTables; $tableIndex += 1)
    {
      if (isset($tablesToCreate[$databaseName][$table]))
      {
        $yamlFile = $tablesToCreate[$databaseName][$table];
        $createdFile = $fixtureFileNameBeginning . $table . '.sql';

        if (file_exists($createdFile))
          echo 'Fixture file creation aborted : the file ', CLI_WARNING, $databaseName . '_' . $table . '.sql',
          END_COLOR, ' already exists.', PHP_EOL;

        // Gets the fixture data
        $fixturesData = Yaml::parse(file_get_contents($yamlFile));

        if (!isset($fixturesData[$table]))
        {
          echo CLI_WARNING, 'No fixtures available for this table \'', $table, '\'.', END_COLOR, PHP_EOL;

          break;
        }

        createFixture(
          $databaseName,
          $table,
          $fixturesData[$table],
          $schema[$table],
          $tablesOrder,
          $fixturesMemory,
          $createdFile
        );

        executeFixture($databaseName, $table);
        break;
      }
    }

    ++$color;
  }

  echo END_COLOR;
}
