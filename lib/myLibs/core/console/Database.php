<?
/**
 * Framework database functions
 *
 * @author Lionel Péramo
 */
declare(strict_types=1);
namespace { require CORE_PATH . 'console/ConsoleTools.php'; }
namespace lib\myLibs\core\console {

  use lib\ { sf2_yaml\Yaml, myLibs\core\bdd\Sql };
  use config\All_Config;
  use lib\myLibs\core\Session;

  class Database
  {
    // Database connection
    private static $host,
      $user,
      $pwd,
      $base,
      $motor,

      // commands beginning
      $command = '',
      $baseCommand = '',
      $initCommand = '',

      // paths
      $pathSql = '',
      $pathYml = '',
      $pathYmlFixtures = '',
      $databaseFile = 'database_schema',
      $schemaFile = 'schema.yml',
      $fixturesFile = 'db_fixture',
      $fixturesFileIdentifiers = 'ids',
      $tablesOrderFile = 'tables_order.yml',
      $fixtureFolder,
      $frameworkName = 'lpframework',

      // just in order to simplify the code
      $attributeInfos = [];

    /** Initializes paths, commands and connections
     *
     * @param string $dbConnKey Database connection key from the general configuration
     *
     * @return bool | void
     */
    public static function init(string $dbConnKey = null)
    {
      self::initBase();
      define('VERBOSE', All_Config::$verbose);
      $dbConn = All_Config::$dbConnections;
      $dbConnKey = null === $dbConnKey ? key($dbConn) : $dbConnKey;

      if (isset($dbConn[$dbConnKey]))
      {
        $infosDb = $dbConn[key($dbConn)];
        self::$user = $infosDb['login'];
        self::$pwd = $infosDb['password'];
        self::$base = $infosDb['db'];

        if (isset($infosDb['motor']))
          self::$motor = $infosDb['motor'];
        else
        {
          echo 'You haven\'t specified the database engine in your configuration file.';

          return false;
        }
      } else
      {
        echo 'You haven\'t specified any database configuration in your configuration file.';

        return false;
      }

      self::$pathYmlFixtures = self::$pathYml . 'fixtures/';
      self::$initCommand = 'mysql --login-path=' . self::$frameworkName . (VERBOSE ? ' --show-warnings' : '');
      self::$baseCommand = self::$initCommand . ' -D ' . self::$base . (VERBOSE > 1 ? ' -v' : '');
      self::$command = self::$baseCommand . ' -e "source ' . self::$pathSql;

      self::$initCommand .= ((VERBOSE > 1) ? ' -v -e "source ' : ' -e "source ') . self::$pathSql;

      self::$fixtureFolder = self::$pathYmlFixtures . self::$fixturesFileIdentifiers . '/';

      if (false === file_exists(self::$fixtureFolder))
        mkdir(self::$fixtureFolder, 0777, true);

      // If we haven't store the database identifiers yet, store them ... only asking for password.
      if ('' === cli('mysql_config_editor print --login-path=' . self::$frameworkName, 0))
      {
        echo 'You will have to type only one time your password by hand, it will be then stored and we\'ll never ask for it in the future.', PHP_EOL;
        cli('mysql_config_editor set --login-path=' . self::$frameworkName . ' --host=' . self::$host . ' --user=' . self::$user . ' --password', VERBOSE);
      }
    }

    /**
     * Initializes main paths :
     * - configuration path : $pathSql, $pathYml
     * - output path : $pathSQL
     * - schema file path : $schemaFile
     * - tables order path : $tablesOrderFile
     */
    public static function initBase()
    {
      self::$pathSql = realpath(BASE_PATH . 'config/data');
      self::$pathYml = self::$pathSql . '/yml/';
      self::$pathSql .= '/sql/';
      self::$schemaFile = self::$pathYml . self::$schemaFile;
      self::$tablesOrderFile = self::$pathYml . self::$tablesOrderFile;
    }

    /**
     * Cleans sql and yml files in the case where there are problems that had corrupted files.
     */
    public static function clean(bool $extensive = false)
    {
      self::initBase();
      $dbFixturePath = self::$pathSql . self::$fixturesFile;

      if (file_exists($dbFixturePath))
      {
        array_map('unlink', glob($dbFixturePath . '/*.sql'));
        rmdir($dbFixturePath);
      }

      array_map('unlink', glob(self::$pathSql . '/*.sql'));
      array_map('unlink', glob(self::$pathSql . 'truncate/*.sql'));

      if (true === $extensive && true === file_exists(self::$tablesOrderFile))
        unlink(self::$tablesOrderFile);

      echo lightGreenText(($extensive) ? 'Full cleaning done.' : 'Cleaning done.'), PHP_EOL;
    }

    /**
     * Runs or creates & runs the database schema file
     *
     * @param string $databaseName Database name
     * @param bool   $force        If true, we erase the database before the tables creation.
     */
    public static function createDatabase( string $databaseName, bool $force = false)
    {
      if (true === $force)
      {
        self::dropDatabase($databaseName);
        self::generateSqlSchema($databaseName, true);
        cli(self::$initCommand . self::$databaseFile . '_force.sql "', VERBOSE);
      } else
      {
        self::generateSqlSchema($databaseName, false);
        cli(self::$initCommand . self::$databaseFile . '.sql "', VERBOSE);
      }

      /** TODO Find a solution on how to inform the final user that there is problems or no via the mysql command. */
      echo lightGreen(), 'Database ', lightCyanText($databaseName), lightGreenText(' created.'), PHP_EOL;
    }

    /**
     * Returns the attribute (notnull, type, primary etc.) in uppercase if it exists
     *
     * @param string $attr Attribute
     * @param bool   $show If we show the information. Default : false
     *
     * @return string $attr Concerned attribute in uppercase
     */
    public static function getAttr( string $attr, bool $show = false) : string
    {
      $output = '';

      if (true === isset(self::$attributeInfos[$attr]))
      {
        if ('notnull' === $attr)
          $attr = 'not null';
        else if ('type' === $attr && false !== strpos(self::$attributeInfos[$attr],
            'string')
        )
          return 'VARCHAR' . substr(self::$attributeInfos[$attr],
            6);

        $output .= true === $show ? ' ' . strtoupper($attr)
          : strtoupper(self::$attributeInfos[$attr]);
      }

      return $output;
    }

    /**
     * Sort the tables that have relations with other tables using the foreign keys
     *
     * @param array $tablesWithRelations Remaining tables to sort
     * @param array $sortedTables        Final sorted tables array
     * @param int   $oldCountArrayToSort
     *
     * @return bool
     */
    private static function _sortTableByForeignKeys(
      array $tablesWithRelations,
      array &$sortedTables,
      int $oldCountArrayToSort = 0)
    {
      $nextArrayToSort = $tablesWithRelations;

      foreach ($tablesWithRelations as $table => $properties)
      {
        $add = ['valid' => true];

        // Are the relations of $properties['relations'] all in $sortedTables or are they recursive links (e.g. : parent property) ?
        foreach (array_keys($properties['relations']) as $relation)
        {
          $alreadyExists = (in_array($relation,
              $sortedTables) || $relation === $table);
          /* If there is at least one problem because one foreign key references an non-existent table ...
             => that's invalid ...we put false */
          $add['valid'] = $add['valid'] && $alreadyExists;
        }

        /* If all the tables related to foreign keys are already known,
           we add it to the other tables because we can do these relations safely */
        if (true === $add['valid'])
        {
          $sortedTables[] = $table;
          unset($nextArrayToSort[$table]);
        }
      }

      $countArrayToSort = count($nextArrayToSort);

      /* Fix for the "recursive" tables */
      if ($oldCountArrayToSort == $countArrayToSort)
      {
        $sortedTables[] = $table;

        return true;
      }

      // If it remains some tables to sort we re-launch the function
      if (0 < $countArrayToSort)
        self::_sortTableByForeignKeys($nextArrayToSort,
          $sortedTables,
          $countArrayToSort);
    }

    /**
     * Create the sql content of the wanted fixture. We only allow one table per file for simplicity and performance.
     *
     * @param string $databaseName   The database name to use
     * @param string $table          The table name relating to the fixture to create
     * @param array  $fixturesData   The table fixtures
     * @param array  $tableData      The table data form the database schema in order to have the properties type
     * @param array  $sortedTables   Final sorted tables array
     * @param array  $fixturesMemory An array that stores foreign identifiers in order to resolve yaml aliases
     * @param string $createdFile    Name of the fixture file that will be created.
     */
    public static function createFixture(
      string $databaseName,
      string $table,
      array $fixturesData,
      array $tableData,
      array $sortedTables,
      array &$fixturesMemory,
      string $createdFile
    )
    {
      $first = true;
      $ymlIdentifiers = $table . ': ' . PHP_EOL;
      $tableSql = 'USE ' . $databaseName . ';' . PHP_EOL . 'SET NAMES UTF8;' . PHP_EOL . PHP_EOL . 'INSERT INTO `' . $table . '` (';
      $localMemory = $values = $properties = [];
      $theProperties = '';

      $i = 1; // The database ids begin to 1 by default

      foreach (array_keys($fixturesData) as &$fixtureName)
      {
        $ymlIdentifiers .= '  ' . $fixtureName . ': ' . $i++ . PHP_EOL;
      }

      $fp = fopen(self::$fixtureFolder . $databaseName . '_' . $table . '.yml',
        'w');
      fwrite($fp,
        $ymlIdentifiers);
      fclose($fp);

      echo 'Data  ', lightGreenText('[YML IDENTIFIERS] ');

      /**
       * If this table have relations, we store all the data from the related tables in $fixtureMemory array.
       * TODO Maybe we can store less things in this variable.
       */
      if (true === isset($tableData['relations']))
      {
        foreach (array_keys($tableData['relations']) as &$relation)
        {
          $data = Yaml::parse(file_get_contents(self::$pathYmlFixtures . self::$fixturesFileIdentifiers . '/' . $databaseName . '_' . $relation . '.yml'));

          foreach ($data as $otherTable => &$otherTableData)
          {
            $fixturesMemory[$otherTable] = $otherTableData;
          }
        }
      }

      /**
       * THE REAL, COMPLICATED, WORK BEGINS HERE.
       */

      $i = 1; // The database ids begin to 1 by default

      foreach ($fixturesData as $fixtureName => $properties)
      {
        $i++;
        $localMemory[$fixtureName] = $i;
        $theValues = '';

        foreach ($properties as $property => $value)
        {
          if (in_array($property,
              $sortedTables) && !isset($tableData['relations'][$property])
          )
          {
            echo 'It lacks a relation to the table `' . $table . '` for a `' . $property . '` like property';
            exit(1);
          }

          // If the property refers to an other table, then we search the corresponding foreign key name (eg. : lpcms_module -> 'module1' => fk_id_module -> 4 )
          $theProperties .= '`' .
            (in_array($property,
              $sortedTables)
              ? $tableData['relations'][$property]['local']
              : $property) .
            '`, ';

          $properties [] = $property;

          if (false === in_array($property,
              $sortedTables)
          )
          {
            if (true === is_bool($value))
              $value = $value ? 1 : 0;
            else if (is_string($value) && 'int' == $tableData['columns'][$property]['type'])
              $value = $localMemory[$value];

            $theValues .= (null === $value)
              ? 'NULL, '
              : (is_string($value)
                ? '\'' . addslashes($value) . '\', '
                : $value . ', ');
          } else
          {
            $theValues .= $fixturesMemory[$property][$value] . ', ';
          }

          $values [] = [$fixtureName => $value];
        }

        if (true === $first)
          $tableSql .= substr($theProperties,
              0,
              -2) . ') VALUES';

        $tableSql .= '(' . substr($theValues,
            0,
            -2) . '),';

        $first = false;
      }

      $tableSql = substr($tableSql,
          0,
          -1) . ';';

      // We create sql file that can generate the fixtures in the BDD
      $fp = fopen($createdFile,
        'x');
      fwrite($fp,
        $tableSql);
      fclose($fp);

      echo lightGreenText('[SQL CREATION] ');
    }

    /**
     * Creates all the fixtures for the specified database
     *
     * @param string $databaseName Database name !
     * @param int    $mask         1 => we truncate the table before inserting the fixtures,
     *                             2 => we clean the fixtures sql files and THEN we truncate the table before inserting the fixtures
     */
    public static function createFixtures(
      string $databaseName,
      int $mask)
    {
      // Looks for the fixtures file
      if ($folder = opendir(self::$pathYmlFixtures))
      {
        // Analyzes the database schema in order to guess the properties types
        if (false === file_exists(self::$schemaFile))
        {
          echo cyan(), 'You have to create a database schema file in config/data/schema.yml before using fixtures.', endColor();
          exit(1);
        }

        if (false === file_exists(self::$tablesOrderFile))
        {
          echo brownText('You must use the database generation task before using the fixtures !');
          exit(1);
        }

        $dbFixturePath = self::$pathSql . self::$fixturesFile;

        if (false === file_exists($dbFixturePath))
          mkdir($dbFixturePath);

        $schema = Yaml::parse(file_get_contents(self::$schemaFile));
        $tablesOrder = Yaml::parse(file_get_contents(self::$tablesOrderFile));
        $fixtureFileNameBeginning = $dbFixturePath . '/' . $databaseName . '_';

        // We clean the fixtures sql files whether it's needed
        if (2 === $mask)
        {
          array_map('unlink',
            glob($fixtureFileNameBeginning . '*.sql'));
          echo lightGreenText('Fixtures sql files cleaned.'), PHP_EOL;
        }

        $tablesToCreate = [];

        // Browse all the fixtures files
        while (false !== ($file = readdir($folder)))
        {
          if ($file != '.' && $file != '..' && $file != '')
          {
            $file = self::$pathYmlFixtures . $file;

            // If it's not a folder (for later if we want to add some "complex" folder management ^^)
            if (!is_dir($file))
            {
              $tables = self::analyzeFixtures($file);

              // Beautify the array
              foreach ($tables as $table => $file)
              {
                $tablesToCreate[$databaseName][$table] = $file;
              }
            }
          }
        }

        $color = 0;
        $fixturesMemory = [];
        $weNeedToTruncate = 0 < $mask;
        $truncatePath = self::$pathSql . 'truncate';

        if (true === $weNeedToTruncate && false === file_exists($truncatePath))
          mkdir($truncatePath);

        foreach ($tablesOrder as $table)
        {
          echo PHP_EOL, $color % 2 ? cyan() : lightCyan();

          if (true === $weNeedToTruncate)
          {
            // We truncate the tables
            self::truncateTable($databaseName,
              $table);
          }

          for ($i = 0, $cptTables = count($tablesToCreate[$databaseName]); $i < $cptTables; $i += 1)
          {
            if (isset($tablesToCreate[$databaseName][$table]))
            {
              $file = $tablesToCreate[$databaseName][$table];
              $createdFile = $fixtureFileNameBeginning . $table . '.sql';

              if (true === file_exists($createdFile))
              {
                echo 'Fixture file creation aborted : the file ', $databaseName, '_', $table, '.sql already exists.', PHP_EOL;
                exit(0);
              }

              // Gets the fixture data
              $fixturesData = Yaml::parse(file_get_contents($file));

              if (false === isset($fixturesData[$table]))
              {
                echo brownText('No fixtures available for this table \'' . $table . '\'.'), PHP_EOL;

                break;
              }

              self::createFixture($databaseName,
                $table,
                $fixturesData[$table],
                $schema[$table],
                $tablesOrder,
                $fixturesMemory,
                $createdFile);
              self::executeFixture($databaseName,
                $table);
              break;
            }
          }

          ++$color;
        }

        echo endColor();
      }
    }

    /**
     * @param string $file
     * @param string $databaseName Where to execute the SQL file ?
     */
    public static function executeFile(
      string $file,
      string $databaseName = null)
    {
      if (true === file_exists($file))
      {
        self::init($databaseName);
        cli(self::$initCommand . $file,
          VERBOSE);
      } else
      {
        echo 'The file "', $file, '" doesn\'t exist !';
        exit(1);
      }
    }

    /**
     * Executes the sql file for the specified table and database
     *
     * @param string $databaseName The database name
     * @param string $table        The table name
     */
    public static function executeFixture(
      string $databaseName,
      string $table)
    {
      cli(self::$initCommand . self::$fixturesFile . '/' . $databaseName . '_' . $table . '.sql "',
        VERBOSE);
      echo lightGreenText('[SQL EXECUTION]'), PHP_EOL;
    }

    /**
     * Drops the database.
     *
     * @param string $databaseName Database name !
     */
    public static function dropDatabase(string $databaseName)
    {
      $file = 'drop_' . $databaseName . '.sql';
      $pathAndFile = self::$pathSql . $file;

      // If the file that drops database doesn't exist yet...creates it.
      if (false === file_exists($pathAndFile))
      {
        exec('echo DROP DATABASE IF EXISTS ' . $databaseName . '; > ' . $pathAndFile);
        echo lightGreenText('Drop database sql file created : '), lightCyanText($file), PHP_EOL;
      }

      // And drops the database
      cli(self::$initCommand . $file . '"',
        VERBOSE);

      echo lightGreenText('Database '), lightCyanText($databaseName), lightGreenText(' dropped.'), PHP_EOL;
    }

    /**
     * Generates the sql schema
     *
     * @param string $databaseName Database name
     * @param bool   $force        If true, we erase the existing tables
     */
    public static function generateSqlSchema(
      string $databaseName,
      bool $force = false)
    {
      $dbFile = self::$pathSql . self::$databaseFile . ($force ? '_force.sql' : '.sql');

      if (true === file_exists($dbFile))
      {
        echo 'The \'SQL schema\' file already exists.', PHP_EOL;
        exit(1);
      }

      echo 'The \'sql schema\' file doesn\'t exist. Creates the file...', PHP_EOL;
      $sql = ($force) ? 'CREATE DATABASE '
        : 'CREATE DATABASE IF NOT EXISTS ';

      $sql .= $databaseName . ';' . PHP_EOL . PHP_EOL . 'USE ' . $databaseName . ';' . PHP_EOL . PHP_EOL;

      // Gets the database schema
      $schema = Yaml::parse(file_get_contents(self::$schemaFile));

      // $tableSql contains all the SQL for each table, indexed by table name
      $tableSql = $tablesWithRelations = $sortedTables = [];
      $constraints = '';

      // For each table
      foreach ($schema as $table => &$properties)
      {
        $primaryKeys = [];
        $defaultCharacterSet = '';

        /** @TODO CREATE TABLE IF NOT EXISTS ...AND ALTER TABLE ADD CONSTRAINT IF EXISTS ? */
        $tableSql[$table] = 'CREATE TABLE `' . $table . '` (' . PHP_EOL;

        /**********************
         * COLUMNS MANAGEMENT *
         **********************/

        // For each kind of data (columns, indexes, etc.)
        foreach ($properties as $property => &$attributes)
        {
          if ('columns' === $property)
          {
            // For each column
            foreach ($attributes as $attribute => &$informations)
            {
              self::$attributeInfos = $informations;

              $tableSql[$table] .= '  `' . $attribute . '` '
                . self::getAttr('type')
                . self::getAttr('notnull',
                  true)
                . self::getAttr('auto_increment',
                  true)
                . ',' . PHP_EOL;

              // If the column is a primary key, we add it to the primary keys array
              if (isset(self::$attributeInfos['primary']) && '' !== self::$attributeInfos['primary'])
                $primaryKeys[] = $attribute;
            }
          } else if ('relations' === $property)
          {
            foreach ($attributes as $otherTable => &$attribute)
            {
              // Management of 'ON DELETE XXXX'
              $onDelete = '';

              if (isset($attribute['onDelete']))
                $onDelete = '  ON DELETE ' . strtoupper($attribute['onDelete']);

              $constraints .= 'ALTER TABLE ' . $table . ' ADD CONSTRAINT ' . $attribute['constraint_name']
                . ' FOREIGN KEY(`' . $attribute['local'] . '`)' . PHP_EOL;
              $constraints .= '  REFERENCES ' . $otherTable . '(`' . $attribute['foreign'] . '`)' . PHP_EOL
                . $onDelete . ';' . PHP_EOL;
            }
          } else if ('indexes' === $property)
          {
            /** @TODO Manage the indexes part */
          } else if ('default_character_set' === $property)
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

          foreach ($primaryKeys as &$primaryKey)
          {
            $primaries .= $primaryKey . '`, `';
          }

          $tableSql[$table] .= '  PRIMARY KEY(' . substr($primaries,
              0,
              -3) . ')';
        }

        // Cleaning memory...
        unset($primaries, $primaryKey);

        /************************
         * RELATIONS MANAGEMENT *
         ************************/

        if ($hasRelations = isset($properties['relations']))
        {
          foreach ($properties['relations'] as $key => &$relation)
          {
            if (false === isset($relation['local']))
            {
              echo 'You don\'t have specified a local key for the constraint concerning table ' . $key;
              exit(1);
            }

            if (false === isset($relation['foreign']))
            {
              echo 'You don\'t have specified a foreign key for the constraint concerning table ' . $key;
              exit(1);
            }

            // No problems. We can add the relations to the SQL.
            $tableSql[$table] .= ',' . PHP_EOL . '  CONSTRAINT ' .
              (isset($relation['constraint_name'])
                ? $relation['constraint_name']
                : $relation['local'] . '_to_' . $relation['foreign']
              ) . ' FOREIGN KEY (' . $relation['local'] . ')' . ' REFERENCES ' . $key . '(' . $relation['foreign'] . ')';
          }
        }

        // We add the default character set (UTF8) and the ENGINE define in the framework configuration
        $tableSql[$table] .= PHP_EOL . ('' == $defaultCharacterSet ? ') ENGINE=' . self::$motor . ' DEFAULT CHARACTER SET utf8' : ') ENGINE=' . self::$motor . ' DEFAULT CHARACTER SET ' . $defaultCharacterSet);

        $tableSql[$table] .= ';' . PHP_EOL . PHP_EOL;

        /**
         * We separate
         * the tables with no relations with other tables (that doesn't need to be sorted)
         * from the tables that have relations with other tables (that need to be sorted)
         */
        if (true === $hasRelations)
          $tablesWithRelations[$table] = $schema[$table];
        else
          $sortedTables[] = $table;
      }

      // We sort tables that need sorting
      self::_sortTableByForeignKeys($tablesWithRelations,
        $sortedTables);

      $sqlCreateSection = $sqlDropSection = $tablesOrder = '';
      $storeSortedTables = ($force || false === file_exists(self::$tablesOrderFile));

      // We use the information on the order in which the tables have to be created / used to create correctly the final SQL schema file.
      foreach ($sortedTables as $sortedTable)
      {
        // We store the names of the sorted tables into a file in order to use it later
        if ($storeSortedTables)
          $tablesOrder .= '- ' . $sortedTable . PHP_EOL;

        /* We create the 'create' section of the sql schema file */
        $sqlCreateSection .= $tableSql[$sortedTable];

        /* We create the 'drop' section of the sql schema file */
        $sqlDropSection = ' `' . $sortedTable . '`,' . PHP_EOL . $sqlDropSection;
      }

      /** TODO Test on unix systems if the value 3 is correct or not */
      $sql .= 'DROP TABLE IF EXISTS' . substr($sqlDropSection,
          0,
          -3) . ';' . PHP_EOL . PHP_EOL . $sqlCreateSection;

      // We generates the file that precise the order in which the tables have to be created / used if needed.
      // (asked explicitly by user when overwriting the database or when the file simply doesn't exist)
      if ($storeSortedTables)
      {
        $fp = fopen(self::$tablesOrderFile,
          'w');
        fwrite($fp,
          $tablesOrder);
        fclose($fp);

        echo lightGreenText('\'Tables order\' sql file created : '), lightCyanText(basename(self::$tablesOrderFile)), PHP_EOL;
      }

      /** DROP TABLE MANAGEMENT */

      // We create the SQL schema file with the generated content.
      $fp = fopen($dbFile,
        'w');
      fwrite($fp,
        $sql);
      fclose($fp);

      echo lightGreenText('SQL schema file created.'), PHP_EOL;
    }

    /**
     * Truncates the specified table in the specified database
     *
     * @param string $databaseName Database name
     * @param string $tableName    Table name
     */
    public static function truncateTable(
      string $databaseName,
      string $tableName)
    {
      $file = 'truncate/' . $databaseName . '_' . $tableName . '.sql';
      $pathAndFile = self::$pathSql . $file;

      echo lightCyanText($databaseName . '.' . $tableName), PHP_EOL, 'Table ';

      // If the file that truncates the table doesn't exist yet...creates it.
      if (false === file_exists($pathAndFile))
      {
        $fp = fopen($pathAndFile,
          'x');
        fwrite($fp,
          'USE ' . $databaseName . ';' . PHP_EOL .
          'SET FOREIGN_KEY_CHECKS = 0;' . PHP_EOL .
          'TRUNCATE TABLE ' . $tableName . ';' . PHP_EOL .
          'SET FOREIGN_KEY_CHECKS = 1;');
        fclose($fp);
        echo greenText('[SQL CREATION] ');
      }

      // And truncates the table
      cli(self::$initCommand . $file . '"',
        VERBOSE);
      echo greenText('[TRUNCATED]'), PHP_EOL;
    }

    /**
     * Analyze the fixtures contained in the file and return the found table names
     *
     * @param string $file Fixture file name to analyze
     *
     * @return array The found table names
     */
    private static function analyzeFixtures(string $file)
    {
      // Gets the fixture data
      $fixturesData = Yaml::parse(file_get_contents($file));

      // For each table
      foreach (array_keys($fixturesData) as $table)
      {
        $tablesToCreate[$table] = $file;
      }

      return $tablesToCreate;
    }

    /**
     * Ensures that the configuration to use and the database name are correct. Ensures also that the specified database exists.
     *
     * TODO Think to (re)add the HHVM like notation ?string to allow either a string either a null variable for the two parameters
     *
     * @param string $database  (optional)
     * @param string $confToUse (optional)
     *
     * @return mixed Returns a SQL instance.
     */
    private static function initImports(
      &$database,
      &$confToUse)
    {
      if (null == $confToUse)
        $confToUse = key(All_Config::$dbConnections);

      if (null == $database)
        $database = All_Config::$dbConnections[$confToUse]['db'];

      Session::set('db',
        $confToUse);
      $db = Sql::getDB();

      // Checks if the database concerned exists
      if (false === in_array($database,
          $db->valuesOneCol($db->query('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA')))
      )
      {
        echo 'This database doesn\'t exist.';

        return false;
      }

      return $db;
    }

    /**
     * Creates the database schema from a database.
     * TODO Think to (re)add the HHVM like notation ?string to allow either a string either a null variable for the two parameters
     *
     * @param string $database  (optional)
     * @param string $confToUse (optional)
     *
     * @return bool
     */
    public static function importSchema( string $database = null, string $confToUse = null)
    {
      $db = self::initImports($database, $confToUse);

      $content = '';

      foreach ($db->valuesOneCol($db->query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = \'' . $database . '\'')) as $table)
      {
        $content .= $table . ': ' . PHP_EOL;
        $cols = $db->values($db->query('SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = \'' . $database . '\' AND TABLE_NAME = \'' . $table . '\''));

        // If there are columns ...
        if (0 < count($cols))
          $content .= '  columns:' . PHP_EOL;

        // For each column in this table, we set the different properties
        foreach ($cols as $col)
        {
          $content .= '    ' . $col['COLUMN_NAME'] . ':' . PHP_EOL;
          $content .= '      type: ' . $col['COLUMN_TYPE'] . PHP_EOL;

          if ('NO' == $col['IS_NULLABLE'])
            $content .= '      notnull: true' . PHP_EOL;

          if (false !== strpos($col['EXTRA'],
              'auto_increment')
          )
            $content .= '      auto_increment: true' . PHP_EOL;

          if ('PRI' == $col['COLUMN_KEY'])
            $content .= '      primary: true' . PHP_EOL;
        }

        $constraints = $db->values($db->query('
          SELECT REFERENCED_TABLE_NAME, COLUMN_NAME, REFERENCED_COLUMN_NAME, CONSTRAINT_NAME
          FROM information_schema.KEY_COLUMN_USAGE
          WHERE TABLE_SCHEMA = \'' . $database . '\'
            AND TABLE_NAME = \'' . $table . '\'
            AND CONSTRAINT_NAME <> \'PRIMARY\''));

        // if there are constraints for this table
        if (0 < count($constraints))
        {
          $content .= '  relations:' . PHP_EOL;

          // For each constraint of this table
          foreach ($constraints as $constraint)
          {
            if (false === isset($constraint['REFERENCED_TABLE_NAME']))
              echo 'There is no REFERENCED_TABLE_NAME on ' . (isset($constraint['CONSTRAINT_NAME']) ? $constraint['CONSTRAINT_NAME'] : '/NO CONSTRAINT NAME/') . '.' . PHP_EOL;

            $content .= '    ' . $constraint['REFERENCED_TABLE_NAME'] . ':' . PHP_EOL;
            $content .= '      local: ' . $constraint['COLUMN_NAME'] . PHP_EOL;
            $content .= '      foreign: ' . $constraint['REFERENCED_COLUMN_NAME'] . PHP_EOL;
            $content .= '      constraint_name: ' . $constraint['CONSTRAINT_NAME'] . PHP_EOL;
          }
        }

        $content .= PHP_EOL;
      }

      file_put_contents(self::$schemaFile, $content);
    }

    /**
     * Creates the database fixtures from a database.
     * TODO Think to (re)add the HHVM like notation ?string to allow either a string either a null variable for the two parameters
     *
     * @param string $database  (optional)
     * @param string $confToUse (optional)
     *
     * @return bool
     */
    public static function importFixtures( string $database = null, string $confToUse = null)
    {
      $db = self::initImports($database,
        $confToUse);

      if (false === file_exists(self::$tablesOrderFile))
      {
        echo brownText('You must create the tables order file (' . self::$tablesOrderFile . ') before using this task !');
        exit(1);
      }

      // Everything is in order, we can clean the old files before the process
      array_map('unlink',
        glob(self::$pathYmlFixtures . '*.yml'));

      /** REAL BEGINNING OF THE TASK */

      $tablesOrder = Yaml::parse(file_get_contents(self::$tablesOrderFile));
      $foreignKeysMemory = [];

      foreach ($tablesOrder
               as &$table)
      {
        $content = $table . ': ' . PHP_EOL;
        $cols = $db->values($db->query('SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = \'' . $database . '\' AND TABLE_NAME = \'' . $table . '\''));

        // If there are columns ...
        if (0 < count($cols))
        {
          $sql = 'SELECT ';

          foreach ($cols as &$col)
          {
            $sql .= '`' . $col['COLUMN_NAME'] . '`, ';
          }

          $rows = $db->values($db->query(substr($sql,
              0,
              -2) . ' FROM ' . $database . '.' . $table));

          // If we have results, there is a real interest to create the fixtures (Why create empty files ?!)
          if (0 < count($rows))
          {
            $foreignKeysMemory[$table] = [];

            $constraints = $db->values($db->query('SELECT REFERENCED_TABLE_NAME, COLUMN_NAME, REFERENCED_COLUMN_NAME, CONSTRAINT_NAME
              FROM information_schema.KEY_COLUMN_USAGE
              WHERE TABLE_SCHEMA = \'' . $database . '\'
                AND TABLE_NAME = \'' . $table . '\'
                AND CONSTRAINT_NAME <> \'PRIMARY\'')
            );

            $foreignConstraintsCount = count($constraints);

            foreach ($rows as $key => &$row)
            {
              $fixtureId = $table . '_' . $key;
              $content .= '  ' . $fixtureId . ':' . PHP_EOL;

              foreach ($row as $key => &$colOfRow)
              {
                $content .= '    ';
                // We check if the column has a foreign key assigned or not
                if (0 < $foreignConstraintsCount)
                {
                  $arrayKeyFromConstraints = array_search(
                    $key,
                    array_column($constraints,
                      'COLUMN_NAME')
                  );

                  if (null !== $colOfRow && $key === $constraints[$arrayKeyFromConstraints]['COLUMN_NAME'])
                  {
                    $content .= $constraints[$arrayKeyFromConstraints]['REFERENCED_TABLE_NAME'] . ': ' .
                      $foreignKeysMemory[$constraints[$arrayKeyFromConstraints]['REFERENCED_TABLE_NAME']][(int)$colOfRow] . PHP_EOL;
                    continue;
                  }
                }

                /** We check if the column is a primary key and, if it's the case, we put the name of the actual table
                 * /* and we store the association for later in order to manage the foreign key associations
                 * /****************/
                if ('PRI' === $cols[array_search(
                    $key,
                    array_column($cols,
                      'COLUMN_NAME')
                  )]['COLUMN_KEY']
                )
                {
                  $foreignKeysMemory[$table][$colOfRow] = $fixtureId;
                  //                if($table === )
                  $content .= $key; // $table
                } else // if it's a classic column...
                  $content .= $key;

                $content .= ': ';

                if (null === $colOfRow)
                  $content .= '~';
                else if (is_string($colOfRow))
                {
                  // For some obscure reasons, PHP_EOL cannot work in this case as it is always returning \n in my tests...
                  if (false === strpos($colOfRow,
                      "\n")
                  )
                    $content .= '\'' . str_replace('\'',
                        '\'\'',
                        $colOfRow) . '\'';
                  else // Multi lines text management
                    $content .= '|' . PHP_EOL . '      ' . str_replace("\n",
                        "\n      ",
                        $colOfRow);
                } else
                  $content .= $colOfRow;

                $content .= PHP_EOL;
              }
            }
          }
        }

        // We can now create the fixture file...
        file_put_contents(self::$pathYmlFixtures . $table . '.yml', $content);

        echo green(), 'File ', cyan(), $table . '.yml', greenText(' created'), PHP_EOL;
      }
    }
  }
}

?>
