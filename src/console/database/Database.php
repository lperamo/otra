<?php
/**
 * Framework database functions
 *
 * @author Lionel Péramo
 * @package otra\console\database
 */
declare(strict_types=1);

namespace otra\console\database;

use Exception;
use Symfony\Component\Yaml\Yaml;
use otra\config\AllConfig;
use otra\{bdd\Sql, Session, OtraException};
use const otra\cache\php\{BASE_PATH, BUNDLES_PATH, CONSOLE_PATH, DIR_SEPARATOR};
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, CLI_TABLE, CLI_WARNING, END_COLOR};
use function otra\console\database\sqlExecute\sqlExecute;

/**
 * @package otra\console
 */
abstract class Database
{
  private const
    OTRA_BINARY = 'otra.php',
    OTRA_TASK_SQL_EXECUTE = 'sqlExecute',
    OTRA_DB_PROPERTY_MODE_NOTNULL_AUTOINCREMENT = 0,
    OTRA_DB_PROPERTY_MODE_TYPE = 1,
    OTRA_DB_PROPERTY_MODE_DEFAULT = 2,
    LABEL_FIXTURES = 'fixtures/';

  final public const
    ERROR_CANNOT_CREATE_THE_FOLDER = 'Cannot create the folder ',
    ERROR_CANNOT_REMOVE_THE_FOLDER_SLASH = 'Cannot remove the folder \'',
    ERROR_CLOSE_DIR_FORGOT_CALL = 'Framework note : Maybe you forgot a closedir() call (and then the folder is still used) ? Exception message : ',
    INDENTATION = '  ',
    DOUBLE_INDENTATION = self::INDENTATION . self::INDENTATION;

  // Database connection
  private static string
    $databaseFile = 'database_schema',
    $base,
    $folder = 'bundles/',
    $motor,
    $pathYml = '',
    $password,
    $user;

  private static array
    // just in order to simplify the code
    $attributeInfos = [];

  public static string
    $fixturesFileIdentifiers = 'ids',
    $pathSql = '',
    $pathSqlFixtures,
    $pathYmlFixtures,
    $schemaFile,
    $tablesOrderFile;

  // true if we have initialized the class variables (paths essentially)
  public static bool $init = false;

  /** Initializes paths, commands and connections
   *
   * @param string|null $dbConnKey Database connection key from the general configuration
   *
   * @throws OtraException If there are no database or database engine configured.
   * @return void
   */
  public static function init(string $dbConnKey = null) : void
  {
    $dbConn = AllConfig::$dbConnections;
    $dbConnKey ??= key($dbConn);

    if (!isset($dbConn[$dbConnKey]))
    {
      if (null === $dbConnKey)
        throw new OtraException(
          'You haven\'t specified any database configuration in your configuration file.',
          E_CORE_WARNING
        );

      throw new OtraException(
        'The configuration \'' . $dbConnKey . '\' does not exist in your configuration file.',
        E_CORE_WARNING
      );
    }

    $infosDb = $dbConn[$dbConnKey];

    if (!isset($infosDb['motor']))
      throw new OtraException(
        'You haven\'t specified the database engine in your configuration file.',
        E_CORE_WARNING
      );

    self::initBase();

    if (!defined(__NAMESPACE__ . '\\VERBOSE'))
      define(__NAMESPACE__ . '\\VERBOSE', AllConfig::$verbose);

    self::$base = $infosDb['db'];
    self::$motor = $infosDb['motor'] ?? (Sql::$currentConn)::DEFAULT_MOTOR;
    self::$password = $infosDb['password'];
    self::$user = $infosDb['login'];
    self::$pathYmlFixtures = self::$pathYml . self::LABEL_FIXTURES;
    self::$init = true;
  }

  /**
   * Initializes main paths :
   * - configuration path : $pathSql, $pathYml
   * - output path : $pathSQL
   * - schema file path : $schemaFile
   * - tables order path : $tablesOrderFile
   */
  public static function initBase() : void
  {
    self::$pathYml = BUNDLES_PATH . 'config/';
    self::$pathYmlFixtures = self::$pathYml . self::LABEL_FIXTURES;
    self::$pathSql = BUNDLES_PATH . 'config/data/sql/';
    self::$pathSqlFixtures = self::$pathSql . self::LABEL_FIXTURES;
    self::$schemaFile = self::$pathYml . 'schema.yml';
    self::$tablesOrderFile = self::$pathYml . 'tables_order.yml';
  }

  /**
   * @return string[]
   *
   * @throws OtraException
   */
  public static function getDirs() : array
  {
    $folder = BASE_PATH . self::$folder;

    if (!file_exists($folder))
    {
      echo CLI_ERROR, 'The folder ', CLI_TABLE, 'BASE_PATH + ', CLI_INFO_HIGHLIGHT, self::$folder, CLI_ERROR,
      ' does not exist.', END_COLOR, PHP_EOL;
      throw new OtraException(code: 1, exit: true);
    }

    $folderHandler = opendir($folder);
    $folders = [];

    // We scan the bundles' directory to retrieve all the bundles name ...
    while (false !== ($actualFile = readdir($folderHandler)))
    {
      // 'config' and 'views' are not bundles ...
      if (in_array($actualFile, ['.', '..', 'config', 'views']))
        continue;

      $bundleDir = $folder . $actualFile;

      // We don't need the files either
      if (!is_dir($bundleDir))
        continue;

      $folders[] = $bundleDir . DIR_SEPARATOR;
    }

    closedir($folderHandler);

    return $folders;
  }

  /**
   * Returns the attribute (notnull, type, primary etc.) in uppercase if it exists
   *
   * @param string $attribute Attribute
   * @param int    $mode      How we show the type of date
   *                          0: type
   *                          1: value (default)
   *                          2: type value
   *                          3: comment
   *
   * @return string $attribute Concerned attribute in uppercase
   */
  public static function getAttr(string $attribute, int $mode = self::OTRA_DB_PROPERTY_MODE_TYPE) : string
  {
    if (isset(self::$attributeInfos[$attribute]))
    {
      $value = self::$attributeInfos[$attribute];

      if ('comment' === $attribute)
        return ' COMMENT \'' . $value . '\'';

      if ('notnull' === $attribute)
        $attribute = 'not null';
      elseif ('type' === $attribute && str_contains($value, 'string'))
        return 'VARCHAR' . substr($value, 6);

      if ($mode === self::OTRA_DB_PROPERTY_MODE_NOTNULL_AUTOINCREMENT)
        return ' ' . strtoupper($attribute);
      elseif ($mode === self::OTRA_DB_PROPERTY_MODE_TYPE)
        return strtoupper($value);
      else // self::OTRA_DB_PROPERTY_MODE_DEFAULT
      {
        $isDateTimeOrTimestamp = in_array(strtoupper(self::$attributeInfos['type']),  ['DATETIME', 'TIMESTAMP']);
        $testValue = strtoupper($value);

        return ' ' . strtoupper($attribute) . ' ' .
          (is_string($value) && !$isDateTimeOrTimestamp
            ? '\'' . $value . '\''
            : ($isDateTimeOrTimestamp && ($testValue === 'CURRENT_TIMESTAMP' || $testValue === 'NOW')
              ? $value
              : '\'' . $value . '\'')
          );
      }
    } elseif ($attribute === 'default'
      && isset(self::$attributeInfos['type'])
      && in_array(strtoupper(self::$attributeInfos['type']),  ['DATETIME', 'TIMESTAMP']))
      // We force a default value in case the SQL_MODE requires it to avoid errors
      return ' DEFAULT current_timestamp';

    return '';
  }

  /**
   * Sort the tables that have relations with other tables using the foreign keys
   *
   * @param array<string,array> $tablesWithRelations Remaining tables to sort
   * @param string[]            $sortedTables        Final sorted tables array
   */
  private static function _sortTableByForeignKeys(
    array $tablesWithRelations,
    array &$sortedTables,
    int $oldCountArrayToSort = 0
  ) : void
  {
    if (empty($tablesWithRelations))
      return;

    $nextArrayToSort = $tablesWithRelations;

    foreach ($tablesWithRelations as $tableName => $properties)
    {
      $mustAddTableToSortedTables = ['valid' => true];

      // Are the relations of $properties['relations'] all in $sortedTables or are they recursive links (e.g. : parent
      // property) ?
      foreach (array_column($properties['relations'], 'table') as $relation)
      {
        $alreadyExists = (in_array($relation, $sortedTables) || $relation === $tableName);
        /* If there is at least one problem because one foreign key references a non-existent table ...
           => that's invalid ...we put false */
        $mustAddTableToSortedTables['valid'] = $mustAddTableToSortedTables['valid'] && $alreadyExists;
      }

      /* If all the tables related to foreign keys are already known,
         we add it to the other tables because we can do these relations safely */
      if ($mustAddTableToSortedTables['valid'])
      {
        $sortedTables[] = $tableName;
        unset($nextArrayToSort[$tableName]);
      }
    }

    $countArrayToSort = count($nextArrayToSort);

    /* Fix for the "recursive" tables */
    if ($oldCountArrayToSort == $countArrayToSort && !in_array($tableName, $sortedTables))
    {
      $sortedTables[] = $tableName;

      return;
    }

    // If it remains some tables to sort we re-launch the function
    if (0 < $countArrayToSort)
      self::_sortTableByForeignKeys($nextArrayToSort, $sortedTables, $countArrayToSort);
  }



  /**
   * Drops the database.
   *
   * @param string $databaseName Database name !
   *
   * @return Sql
   *
   * @throws OtraException
   */
  public static function dropDatabase(string $databaseName) : Sql
  {
    $sqlInstance = Sql::getDb(null, false);
    Sql::$instance->beginTransaction();

    try
    {
      $result = Sql::$instance->query('DROP DATABASE IF EXISTS ' . $databaseName);
      Sql::$instance->freeResult($result);
    } catch (Exception $exception)
    {
      Sql::$instance->rollBack();
      throw new OtraException('Procedure aborted. ' . $exception->getMessage());
    }

    Sql::$instance->commit();

    echo CLI_BASE, 'Database ', CLI_INFO_HIGHLIGHT, $databaseName, CLI_BASE, ' dropped', CLI_SUCCESS, ' ✔', END_COLOR,
    PHP_EOL;

    return $sqlInstance;
  }

  /**
   * Generates the sql schema. A YAML schema is required.
   *
   * @param string $databaseName Database name
   * @param bool   $force        If true, we erase the existing tables
   *
   * @return string $dbFile Name of the sql file generated
   *
   * @throws OtraException If the YAML schema doesn't exist.
   *   If there is a missing foreign/local key
   */
  public static function generateSqlSchema( string $databaseName, bool $force = false) : string
  {
    if (!self::$init)
      self::init();

    $dbFile = self::$pathSql . self::$databaseFile . ($force ? '_force.sql' : '.sql');

    // We keep only the end of the path for a cleaner display
    $dbFileLong = substr($dbFile, strlen(BASE_PATH));

    $msgBeginning = 'The \'SQL schema\' file ' . CLI_WARNING . $dbFileLong . END_COLOR;

    if (file_exists($dbFile))
    {
      echo $msgBeginning, ' already exists.';

      return $dbFile;
    }

    echo $msgBeginning, ' does not exist. Creates the file...', PHP_EOL;
    $databaseCreationSql = 'CREATE DATABASE IF NOT EXISTS ';

    $databaseCreationSql .= $databaseName . ';' . PHP_EOL . PHP_EOL . 'USE `' . $databaseName . '`;' . PHP_EOL . PHP_EOL;

    // We check if the YML schema exists
    if (!file_exists(self::$schemaFile))
      throw new OtraException(
        'The file \'' . substr(self::$schemaFile, strlen(BASE_PATH)) .
        '\' does not exist. We can\'t generate the SQL schema without it.',
        E_CORE_ERROR,
        __FILE__,
        __LINE__
      );

    // We ensure us that all the needed folders exist
    if (!file_exists(self::$pathSql))
      mkdir(self::$pathSql, 0777, true);

    /** @var array<string, array{
     *    columns: array<string, array<string, mixed>>,
     *    relations: array<string, array<string,string>>,
     *    indexes: array<string, array<string,string>>
     * }> $schema
     */
    $schema = Yaml::parse(file_get_contents(self::$schemaFile));

    // $tableSql contains all the SQL for each table, indexed by table name
    $tableSql = $tablesWithRelations = $sortedTables = [];

    // For each table
    foreach ($schema as $table => $properties)
    {
      $hasRelations = false;
      $primaryKeys = [];
      $defaultCharacterSet = '';
      $tableSql[$table] = 'CREATE TABLE `' . $table . '` (' . PHP_EOL;

      //**********************
      //* COLUMNS MANAGEMENT *
      //**********************
      // For each kind of data (columns, indexes, etc.)
      foreach ($properties as $property => $attributes)
      {
        if ('columns' === $property)
        {
          // For each column
          foreach ($attributes as $attribute => $informations)
          {
            self::$attributeInfos = $informations;

            $tableSql[$table] .= '  `' . $attribute . '` '
              . self::getAttr('type')
              . self::getAttr('default', self::OTRA_DB_PROPERTY_MODE_DEFAULT)
              . self::getAttr('notnull', self::OTRA_DB_PROPERTY_MODE_NOTNULL_AUTOINCREMENT)
              . self::getAttr('auto_increment', self::OTRA_DB_PROPERTY_MODE_NOTNULL_AUTOINCREMENT)
              . self::getAttr('comment')
              . ',' . PHP_EOL;

            // If the column is a primary key, we add it to the primary keys array
            if (isset(self::$attributeInfos['primary']) && '' !== self::$attributeInfos['primary'])
              $primaryKeys[] = $attribute;
          }
        } elseif ('relations' === $property)
        {
          $hasRelations = true;

          foreach ($attributes as $constraintName => $attribute)
          {
            if (!isset($attribute['table']))
              throw new OtraException(
                'You don\'t have specified a table name for the constraint named ' . $constraintName,
                E_CORE_ERROR
              );

            if (!isset($attribute['local']))
              throw new OtraException(
                'You don\'t have specified a local key for the constraint concerning table ' . $attribute['table'],
                E_CORE_ERROR
              );

            if (!isset($attribute['foreign']))
              throw new OtraException(
                'You don\'t have specified a foreign key for the constraint concerning table '  . $attribute['table'],
                E_CORE_ERROR
              );

            // Management of 'ON DELETE XXX' and of 'ON UPDATE XXX'
            $onModifier = '';

            if (isset($attribute['on_delete']))
              $onModifier = ' ON DELETE ' . strtoupper($attribute['on_delete']);

            if (isset($attribute['on_update']))
              $onModifier .= ' ON UPDATE ' . strtoupper($attribute['on_update']);

            // No problems. We can add the relations to the SQL.
            $tableSql[$table] .=
              '  CONSTRAINT ' . ($constraintName ?? $attribute['local'] . '_to_' . $attribute['foreign']) .
              ' FOREIGN KEY (' . $attribute['local'] . ')' . ' REFERENCES ' . $attribute['table'] . '(' .
              $attribute['foreign'] . ')' . $onModifier . ',' . PHP_EOL;
          }
        } elseif ('indexes' === $property)
        {
          foreach ($attributes as $indexName => $indexValues)
          {
            $tableSql[$table] .= '  ' . (isset($indexValues['category'])
                ? strtoupper($indexValues['category']) . ' '
                : ''
              ) . 'INDEX `' . $indexName . '` (';
            foreach ($indexValues['columns'] as $columnKey => $column)
            {
              $tableSql[$table] .= '`' . $column;
              $tableSql[$table] .= (array_key_last($indexValues['columns']) !== $columnKey)
                ? '`,'
                : '`';
            }

            $tableSql[$table] .= '),' . PHP_EOL;
          }
        } elseif ('default_character_set' === $property)
          $defaultCharacterSet = $attributes;
      }

      // Cleaning memory...
      unset($property, $attributes, $informations, $otherTable, $attribute);

      /***************************
       * PRIMARY KEYS MANAGEMENT *
       ***************************/
      if (empty($primaryKeys))
        echo 'NOTICE : There isn\'t primary key in ', $table, '!', PHP_EOL;
      else
      {
        $primaries = '`';

        foreach ($primaryKeys as $primaryKey)
        {
          $primaries .= $primaryKey . '`, `';
        }

        $tableSql[$table] .= '  PRIMARY KEY(' . substr($primaries, 0, -3) . ')';
      }

      // Cleaning memory...
      unset($primaries, $primaryKey);

      // We add the default character set (utf8mb4) and the ENGINE define in the framework configuration
      $tableSql[$table] .= PHP_EOL . ('' === $defaultCharacterSet ? ') ENGINE=' . self::$motor .
          ' DEFAULT CHARACTER SET utf8mb4' : ') ENGINE=' . self::$motor . ' DEFAULT CHARACTER SET ' . $defaultCharacterSet);
      $tableSql[$table] .= ';';

      /**
       * We separate
       * the tables with no relations with other tables (that doesn't need to be sorted)
       * from the tables that have relations with other tables (that need to be sorted)
       */
      if ($hasRelations)
        $tablesWithRelations[$table] = $properties;
      else
        $sortedTables[] = $table;
    }

    // We sort tables that need sorting
    self::_sortTableByForeignKeys($tablesWithRelations, $sortedTables);

    $sqlCreateSection = $sqlDropSection = $tablesOrder = '';
    $storeSortedTables = ($force || !file_exists(self::$tablesOrderFile));

    // We use the information on the order in which the tables have to be created / used to create correctly the final
    // SQL schema file.
    /**
     * @var int|string $arrayIndex
     * @var string     $sortedTable
     */
    foreach ($sortedTables as $arrayIndex => $sortedTable)
    {
      // We store the names of the sorted tables into a file in order to use it later
      if ($storeSortedTables)
        $tablesOrder .= '- ' . $sortedTable . PHP_EOL;

      /* We create the 'create' section of the sql schema file */
      $sqlCreateSection .= $tableSql[$sortedTable];

      if ($arrayIndex !== array_key_last($sortedTables))
        $sqlCreateSection .= PHP_EOL . PHP_EOL;

      /* We create the 'drop' section of the sql schema file */
      $sqlDropSection = ' `' . $sortedTable . '`,' . PHP_EOL . $sqlDropSection;
    }

    /** DROP TABLE MANAGEMENT */
    $databaseCreationSql .= 'DROP TABLE IF EXISTS' . substr($sqlDropSection, 0, -strlen(',' . PHP_EOL)) .
      ';' . PHP_EOL . PHP_EOL . $sqlCreateSection;

    // We generate the file that precise the order in which the tables have to be created / used if needed.
    // (asked explicitly by user when overwriting the database or when the file simply doesn't exist)
    if ($storeSortedTables)
    {
      file_put_contents(self::$tablesOrderFile, $tablesOrder);
      echo CLI_BASE, '\'Tables order\' sql file created : ', CLI_INFO_HIGHLIGHT,
      basename(self::$tablesOrderFile), CLI_SUCCESS, ' ✔', END_COLOR, PHP_EOL;
    }

    // We create the SQL schema file with the generated content.
    file_put_contents($dbFile, $databaseCreationSql . PHP_EOL);

    echo CLI_BASE, 'SQL schema file created', CLI_SUCCESS, ' ✔', END_COLOR, PHP_EOL;

    return $dbFile;
  }

  /**
   * Truncates the specified table in the specified database
   *
   * @param string $databaseName Database name
   * @param string $tableName    Table name
   *
   * @throws OtraException If we cannot create the truncate folder.
   * If we cannot truncate the table.
   */
  public static function truncateTable(string $databaseName, string $tableName) : void
  {
    if (!self::$init)
      self::initBase();

    $truncatePath = self::$pathSql . 'truncate/';

    if (!file_exists($truncatePath) && !mkdir($truncatePath, 0777, true))
      throw new OtraException(self::ERROR_CANNOT_CREATE_THE_FOLDER . $truncatePath);

    $sqlFile = $databaseName . '_' . $tableName . '.sql';
    $pathAndFile = $truncatePath . $sqlFile;

    echo CLI_INFO_HIGHLIGHT, $databaseName, '.', $tableName, END_COLOR, PHP_EOL, 'Table ';

    // If the file that truncates the table doesn't exist yet...creates it.
    if (!file_exists($pathAndFile))
    {
      file_put_contents(
        $pathAndFile,
        'USE `' . $databaseName . '`;' . PHP_EOL .
        'SET FOREIGN_KEY_CHECKS = 0;' . PHP_EOL .
        'TRUNCATE TABLE ' . $tableName . ';' . PHP_EOL .
        'SET FOREIGN_KEY_CHECKS = 1;' . PHP_EOL
      );
      echo CLI_SUCCESS, '[SQL CREATION] ', END_COLOR;
    }

    // And truncates the table (using 'once' as we can execute other things later)
    require_once CONSOLE_PATH . 'database/sqlExecute/sqlExecuteTask.php';
    sqlExecute([self::OTRA_BINARY, self::OTRA_TASK_SQL_EXECUTE, $truncatePath . $sqlFile]);

    echo CLI_SUCCESS, '[TRUNCATED]', END_COLOR, PHP_EOL;
  }

  /**
   * Ensures that the configuration to use and the database name are correct.
   * Ensures also that the specified database exists.
   *
   * @param string|null $databaseName  (optional)
   * @param string|null $confToUse (optional)
   *
   * @throws OtraException If the database doesn't exist.
   * @return bool|Sql Returns an SQL instance.
   */
  public static function _initImports(?string &$databaseName, ?string &$confToUse) : bool|Sql
  {
    if (null === $confToUse)
      $confToUse = key(AllConfig::$dbConnections);

    if (null === $databaseName)
      $databaseName = AllConfig::$dbConnections[$confToUse]['db'];

    Session::set('db', $confToUse);
    $database = Sql::getDb(null, false);

    $schemaInformations = $database->valuesOneCol(
      $database->query('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA')
    );

    // Checks if the database concerned exists.
    // We check lowercase in case the database has converted the name to lowercase
    if (!in_array(strtolower($databaseName), $schemaInformations)
      && !in_array($databaseName, $schemaInformations))
      throw new OtraException('The database \'' . $databaseName . '\' does not exist.', E_CORE_ERROR);

    return $database;
  }
}
