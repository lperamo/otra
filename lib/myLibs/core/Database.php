<?
/**
 * Framework database functions
 *
 * @author Lionel Péramo
 */
namespace lib\myLibs\core;

use lib\sf2_yaml\Parser,
    lib\sf2_yaml\Yaml,
    config\All_Config,
    lib\myLibs\core\bdd\Sql;

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
    $attributeInfos = array();

  /** Initalizes paths, commands and connections */
  public static function init()
  {
    self::initBase();
    define('VERBOSE', All_Config::$verbose);
    $dbConn = All_Config::$dbConnections;

    if(isset($dbConn[key($dbConn)]))
    {
      $infosDb = $dbConn[key($dbConn)];
      self::$user = $infosDb['login'];
      self::$pwd = $infosDb['password'];
      self::$base = $infosDb['db'];

      if(isset($infosDb['motor']))
        self::$motor = $infosDb['motor'];
      else {
        echo 'You haven\'t specified the database engine in your configuration file.';
        return false;
      }
    } else {
      echo 'You haven\'t specified any database configuration in your configuration file.';
      return false;
    }


    self::$pathYmlFixtures = self::$pathYml . 'fixtures/';
    self::$initCommand = 'mysql --login-path=' . self::$frameworkName . (VERBOSE ? ' --show-warnings' : '');
    $finCommande = ' -e "source ' . self::$pathSql;

    self::$command =  self::$initCommand . ' -D ' . self::$base . ((VERBOSE > 1) ? ' -v' . $finCommande : $finCommande);

    self::$initCommand .= ((VERBOSE > 1) ? ' -v -e "source ' : ' -e "source ') . self::$pathSql;

    self::$fixtureFolder = self::$pathYmlFixtures . self::$fixturesFileIdentifiers . '/';

    if(!file_exists(self::$fixtureFolder))
      mkdir(self::$fixtureFolder);

    // If we haven't store the database identifiers yet, store them ... only asking for password.
    if('' == Script_Functions::cli('mysql_config_editor print --login-path=' . self::$frameworkName, 0))
    {
      echo 'You will have to type only one time your password by hand, it will be then stored and we\'ll never ask for it in the future.', PHP_EOL;
      Script_Functions::cli('mysql_config_editor set --login-path=' . self::$frameworkName . ' --host=' . self::$host . ' --user=' . self::$user . ' --password', VERBOSE);
    }
  }

  /** Initializes main paths */
  public static function initBase()
  {
    self::$pathSql = _DIR_ . '/../config/data/';
    self::$pathYml = self::$pathSql . 'yml/';
    self::$pathSql .= 'sql/';
    self::$schemaFile = self::$pathYml . self::$schemaFile;
    self::$tablesOrderFile = self::$pathYml . self::$tablesOrderFile;
  }

  /** Cleans sql and yml files in the case where there are problems that had corrupted files. */
  public static function clean($extensive = false)
  {
    self::initBase();
    array_map('unlink', glob(self::$pathSql . '*'));

    if($extensive && file_exists(self::$tablesOrderFile))
      unlink(self::$tablesOrderFile);

    echo ($extensive) ? 'Full cleaning done.' : 'Cleaning done.', PHP_EOL;
  }

  /**
   * Runs or creates & runs the database schema file
   *
   * @param string   $databaseName Database name
   * @param bool     $force        If true, we erase the database before the tables creation.
   */
  public static function createDatabase($databaseName, $force = false)
  {
    if ($force)
    {
      self::dropDatabase($databaseName);
      self::generateSqlSchema($databaseName, true);
      Script_Functions::cli(self::$initCommand . self::$databaseFile . '_force.sql "', VERBOSE);
    } else {
      self::generateSqlSchema($databaseName, false);
      Script_Functions::cli(self::$initCommand . self::$databaseFile . '.sql "', VERBOSE);
    }

    echo 'Database created.', PHP_EOL;
  }

  /**
   * Returns the attribute in uppercase if it exists
   *
   * @param string $attr  Attribute
   * @param bool   $show  If we show the information. Default : false
   *
   * @return string $attr Concerned attribute in uppercase
   */
  public static function getAttr($attr, $show = false)
  {
    $output = '';
    if(isset(self::$attributeInfos[$attr]))
    {
      if('notnull' == $attr)
        $attr = 'not null';
      else if('type' == $attr && false !== strpos(self::$attributeInfos[$attr], 'string'))
        return 'VARCHAR'.substr(self::$attributeInfos[$attr], 6);

      $output .= ($show) ? ' '.strtoupper($attr)
                         : strtoupper(self::$attributeInfos[$attr]);
    }

    return $output;
  }

  /**
   * Sort the tables using the foreign keys
   *
   * @param array $theOtherTables Remaining tables to sort
   * @param array $tables         Final sorted tables array
   */
  private static function _sortTableByForeignKeys(array $theOtherTables, &$tables, $oldCountArrayToSort = 0)
  {
    $nextArrayToSort = $theOtherTables;

    foreach($theOtherTables as $key => $properties)
    {
      foreach($properties['relations'] as $relation => $relationProperties)
      {
        $add = (in_array($relation, $tables));
      }

      if($add)
      {
        $tables[$key] = $key;
        unset($nextArrayToSort[$key]);
      }
    }

    $countArrayToSort = count($nextArrayToSort);

    /* Fix for the "recursive" tables */
    if($oldCountArrayToSort == $countArrayToSort)
    {
      $tables[$key] = $key;
      return true;
    }

    if(0 < $countArrayToSort)
      self::_sortTableByForeignKeys ($nextArrayToSort, $tables, $countArrayToSort);
  }

  /**
   * Create the sql content of the wanted fixture
   *
   * @param string $databaseName  The database name to use
   * @param string $file          The fixture file to parse
   * @param array  $schema        The database schema in order to have the properties type
   * @param array  $sortedTables  Final sorted tables array
   * @param array  $fixtureMemory An array that stores foreign identifiers in order to resolve yaml aliases
   * @param bool   $force         True => we have to truncate the table before inserting the fixtures
   */
  public static function createFixture($databaseName, $file, array $schema, array $sortedTables, &$fixtureMemory = array(), $force = false)
  {
    // Gets the fixture data
    $fixturesData = Yaml::parse(file_get_contents($file));

    $createdFiles = array();
    $first = true;

    // For each table
    foreach ($fixturesData as $table => $names)
    {
      $createdFile = self::$pathSql . self::$fixturesFile . '_' . $databaseName . '_' . $table . '.sql';
      $createdFiles [$table]= $createdFile;

      $localMemory = array();
      $ymlIdentifiers = $table . ': ' . PHP_EOL;

      if($force)
        self::truncateTable($databaseName, $table);

      if (!file_exists($createdFile))
      {
        //$tableSql = 'USE ' . $databaseName . ';' . PHP_EOL . 'SET NAMES UTF8;' . PHP_EOL . PHP_EOL . 'INSERT INTO `' . $table . '` (' . PHP_EOL;
        $tableSql = 'USE ' . $databaseName . ';' . PHP_EOL . 'SET NAMES UTF8;' . PHP_EOL . PHP_EOL . 'INSERT INTO `' . $table . '` (';
        $values = $properties = array();
        $theProperties = '';

        if(isset($schema[$table]['relations']))
        {
          foreach(array_keys($schema[$table]['relations']) as $relation)
          {
            $datas = Yaml::parse(file_get_contents(self::$pathYmlFixtures . self::$fixturesFileIdentifiers . '/' . $databaseName . '_' . $relation . '.yml'));
            foreach($datas as $key => $data) {
              $fixturesMemory[$key] = $data;
            }
          }
        }

        $i = 1; // The database ids begin to 1 by default

        foreach($names as $name => $properties)
        {
          // Allows to put the properties in disorder in the fixture file
          ksort($properties);

          $ymlIdentifiers .= '  ' . $name . ': ' . $i++ . PHP_EOL;
          //$ymlIdentifiers .= '  ' . $name . ': ' . $i++;
          $localMemory[$name] = $i;

          $theValues = '';
          foreach ($properties as $property => $value)
          {
            // If the property refers to a table name, then we search the corresponding foreign key name
            if ($first)
                $theProperties .= (in_array($property, $sortedTables)) ? '`' . $schema[$table]['relations'][$property]['local'] . '`, '
                                                                       : '`' . $property . '`, ';
            $properties [] = $property;

            if (!in_array($property, $sortedTables))
            {
              // if the value is null
              if(null === $value)
              {
                $tmp = $schema[$table]['columns'][$property];
                $tmpBool = isset($tmp['notnull']);

                if(!$tmpBool || ($tmpBool && false === $tmp['notnull']))
                {
                  if (false !== strpos($tmp['type'], 'string'))
                    $value = ' ';

                  switch($tmp['type'])
                  {
                    case 'timestamp' :
                    case 'int' : $value = 0;
                                 break;
                  }
                }
              } else if(is_bool($value))
                  $value = $value ? 1 : 0;
              else if(is_string($value) && 'int' == $schema[$table]['columns'][$property]['type'])
                $value = $localMemory[$value];

              $theValues .= (is_string($value)) ? '\'' . addslashes($value) . '\', ' : $value . ', ';
            } else
              $theValues .= $fixturesMemory[$property][$value] . ', ';

            $values [] = array($name => $value);
          }

          if ($first)
            $tableSql .= substr($theProperties, 0, -2) . ') VALUES';

          $tableSql .= '(' . substr($theValues, 0, -2) . '),';

          $first = false;
        }

        $tableSql  = substr($tableSql, 0, -1) . ';';

        $fp = fopen($createdFile, 'x' );
        fwrite($fp, $tableSql);
        fclose($fp);

        echo 'Fixture file created : ', self::$fixturesFile, '_', $databaseName, '_', $table, '.sql', PHP_EOL;

        $fp = fopen(self::$fixtureFolder . $databaseName . '_' . $table . '.yml', 'w' );
        fwrite($fp, $ymlIdentifiers);
        fclose($fp);
      } else
        echo 'Fixture file creation aborted : the file ' , self::$fixturesFile, '_' , $databaseName , '_' , $table, '.sql', ' already exists.', PHP_EOL;
    }
  }

  /**
   * Creates all the fixtures for the specified database
   *
   * @param string $databaseName Database name !
   * @param bool   $force        If true, we erase the data before inserting
   */
  public static function createFixtures($databaseName, $force = false)
  {
    $folder = '';

    // Looks for the fixtures file
    if ($folder = opendir(self::$pathYmlFixtures))
    {
      // Analyzes the database schema in order to guess the properties types
      if(!file_exists(self::$schemaFile))
      {
        echo cyan() , 'You have to create a database schema file in config/data/schema.yml before using fixtures.' , endColor();
        die;
      }

      $schema = Yaml::parse(file_get_contents(self::$schemaFile));


      if(!file_exists(self::$tablesOrderFile))
      {
        echo cyan() , 'You must use the database generation task before using the fixtures !' , endColor();
        die;
      }

      $tablesOrder = Yaml::parse(file_get_contents(self::$tablesOrderFile));
      $tablesToCreate = array();

      // Browse all the fixtures files
      while(false !== ($file = readdir($folder)))
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
      foreach($tablesOrder as $table)
      {
        echo $color % 2 ? cyan() : lightCyan();

        for ($i = 0, $cptTables = count($tablesToCreate[$databaseName]); $i < $cptTables; $i += 1)
        {
          if(isset($tablesToCreate[$databaseName][$table]))
          {
            $file = $tablesToCreate[$databaseName][$table];
            self::createFixture($databaseName, $file, $schema, $tablesOrder, $fixtureMemory, $force);
            self::executeFixture($databaseName, $table, $file);
            break;
          }
        }

        ++$color;
      }

      echo endColor();
    }
  }

  /**
   * Executes the sql file for the specified table and database
   *
   * @param string $databaseName The database name
   * @param string $table        The table name
   */
  public static function executeFixture($databaseName, $table)
  {
    Script_Functions::cli(self::$initCommand . self::$fixturesFile . '_' . $databaseName .'_' . $table . '.sql "', VERBOSE);
  }

  /**
   * Drops the database.
   *
   * @param string $databaseName Database name !
   */
  public static function dropDatabase($databaseName)
  {
    $file = 'drop_' . $databaseName.'.sql';
    $pathAndFile = self::$pathSql . $file;

    // If the file that drops database doesn't exist yet...creates it.
    if (!file_exists($pathAndFile))
    {
      exec('echo DROP DATABASE IF EXISTS ' . $databaseName . '; > ' . $pathAndFile);
      echo '\'Drop database\' file created.' , PHP_EOL;
    }

    // And drops the database
    Script_Functions::cli(self::$initCommand . $file . '"', VERBOSE);
    echo 'Database dropped.', PHP_EOL;
  }

  /**
   * Generates the sql schema
   *
   * @param string $databaseName Database name
   * @param bool   $force        If true, we erase the existing tables
   */
  public static function generateSqlSchema($databaseName, $force = false)
  {
    $dbFile = self::$pathSql . self::$databaseFile . ($force ? '_force.sql' : '.sql');

    if (!file_exists($dbFile))
    {
      echo 'The \'sql schema\' file doesn\'t exist. Creates the file...', PHP_EOL;
      $sql = ($force) ? 'CREATE DATABASE '
                      : 'CREATE DATABASE IF NOT EXISTS ';

      $sql .=  $databaseName . ';' . PHP_EOL . PHP_EOL . 'USE ' . $databaseName . ';' . PHP_EOL . PHP_EOL;
//      $sql .= 'SET FOREIGN_KEY_CHECKS = 0;' . PHP_EOL . PHP_EOL;

      // Gets the database schema
      $schema = Yaml::parse(file_get_contents(self::$schemaFile));
      $tableSql = $theOtherTables = $sortedTables = array();
      $constraints = '';

      // For each table
      foreach($schema as $table => $properties)
      {
        $primaryKeys = array();
        $defaultCharacterSet = '';

        /** @TODO CREATE TABLE IF NOT EXISTS ...AND ALTER TABLE ADD CONSTRAINT IF EXISTS ? */
        $tableSql[$table] = 'DROP TABLE IF EXISTS `' . $table . '`;' . PHP_EOL . 'CREATE TABLE `' . $table . '` (' . PHP_EOL;

        // For each kind of data (columns, indexes, etc.)
        foreach($properties as $property => $attributes)
        {
          if('columns' == $property)
          {
            // For each column
            foreach ($attributes as $attribute => $informations)
            {
              self::$attributeInfos = $informations;

              $tableSql[$table] .= '  `' . $attribute . '` '
                . self::getAttr('type')
                . self::getAttr('notnull', true)
                . self::getAttr('auto_increment', true)
                . ',' . PHP_EOL;

              if('' != self::getAttr('primary'))
                $primaryKeys[] = $attribute;
            }
          } else if('relations' == $property)
          {
            foreach ($attributes as $otherTable => $attribute)
            {
              // Management of 'ON DELETE XXXX'
              $onDelete = '';

              if(isset($attribute['onDelete']))
                $onDelete = '  ON DELETE '.strtoupper ($attribute['onDelete']);

              $constraints .= 'ALTER TABLE ' . $table . ' ADD CONSTRAINT ' . $attribute['constraint_name']
                . ' FOREIGN KEY(' . $attribute['local'] . ')' . PHP_EOL;
              $constraints .= '  REFERENCES ' . $otherTable . '(' . $attribute['foreign'] . ')' . PHP_EOL
                . $onDelete . ';' . PHP_EOL;
            }

          } else if('indexes' == $property)
          {
            /** @TODO Manage the indexes part */
          } else if('default_character_set' == $property)
            $defaultCharacterSet = $attributes;
        }

        unset($property, $attributes, $informations, $otherTable, $attribute);

        if(empty($primaryKeys))
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

        unset($primaries, $primaryKey);

        if($hasRelations = isset($properties['relations']))
        {
          foreach($properties['relations'] as $key => $relation)
          {
            if(!isset($relation['local']))
            {
              echo 'You don\'t have specified a local key for the constraint concerning table ' . $key;
              die;
            }

            if(!isset($relation['foreign']))
            {
              echo 'You don\'t have specified a foreign key for the constraint concerning table ' . $key;
              die;
            }

            $tableSql[$table] .= ',' . PHP_EOL .'  CONSTRAINT ' . (isset($relation['constraint_name']) ? $relation['constraint_name'] : $relation['local'] . '_to_' . $relation['foreign']) .
             ' FOREIGN KEY (' . $relation['local'] . ')' . ' REFERENCES ' . $key . '(' . $relation['foreign'] . ')';
          }
        }

        $tableSql[$table] .= PHP_EOL . ('' == $defaultCharacterSet ? ') ENGINE=' . self::$motor . ' DEFAULT CHARACTER SET utf8' : ') ENGINE=' . self::$motor . ' DEFAULT CHARACTER SET ' . $defaultCharacterSet);

        $tableSql[$table] .= ';' . PHP_EOL . PHP_EOL;

        // Sort the tables by foreign keys associations
        if($hasRelations)
          $theOtherTables[$table] = $schema[$table];
        else
          $sortedTables[] = $table;
      }

      //$sql .= $constraints. PHP_EOL. 'SET FOREIGN_KEY_CHECKS = 1;' . PHP_EOL;

      self::_sortTableByForeignKeys($theOtherTables, $sortedTables);

      $tablesOrder = '';
      $storeSortedTables = ($force || !file_exists(self::$tablesOrderFile));

      foreach($sortedTables as $sortedTable)
      {
        // We store the names of the sorted tables into a file in order to use it later
        if ($storeSortedTables)
          $tablesOrder .= '- ' . $sortedTable . PHP_EOL;

        $sql .= $tableSql[$sortedTable];
      }

      if($storeSortedTables)
      {
        $fp = fopen(self::$tablesOrderFile, 'w' );
        fwrite($fp, $tablesOrder);
        fclose($fp);

        echo '\'Tables order\' file created.', PHP_EOL;
      }

      $fp = fopen($dbFile, 'w');
      fwrite($fp, $sql);
      fclose($fp);

      echo '\'SQL schema\' file created.', PHP_EOL;
    }else
      echo 'The \'SQL schema\' file already exists.', PHP_EOL;
  }

  /**
   * Truncates the specified table in the specified database
   *
   * @param string $databaseName Database name
   * @param string $tableName    Table name
   */
  public static function truncateTable($databaseName, $tableName)
  {
    $file = 'truncate_' . $databaseName . '_' . $tableName . '.sql';
    $pathAndFile = self::$pathSql . $file;

    // If the file that truncates the table doesn't exist yet...creates it.
    if (!file_exists($pathAndFile))
    {
      $fp = fopen($pathAndFile, 'x');
      fwrite($fp, 'USE '. $databaseName . ';' . PHP_EOL . 'TRUNCATE TABLE ' . $tableName . ';');
      fclose($fp);
      echo '\'Truncate table\' file created.' , PHP_EOL;
    }

    // And truncates the table
    Script_Functions::cli(self::$initCommand . $file . '"', VERBOSE);
    echo 'Table truncated.', PHP_EOL;
  }

  /**
   * Analyze the fixtures contained in the file and return the found table names
   *
   * @param string $file Fixture file name to analyze
   *
   * @return array The found table names
   */
  private static function analyzeFixtures($file)
  {
    // Gets the fixture data
    $fixturesData = Yaml::parse(file_get_contents($file));

    // For each table
    foreach (array_keys($fixturesData) as $table)
    {
      $tablesToCreate[$table]= $file;
    }

    return $tablesToCreate;
  }

  /**
   * Creates the database schema from a database.
   *
   * @param string $database (optional)
   * @param string $confToUse (optional)
  */
  public static function importSchema($database = null, $confToUse = null)
  {
    error_reporting(0);

    require(__DIR__ . '/../../../lib/myLibs/core/Debug_Tools.php');
    if(null == $confToUse)
      $confToUse = key(All_Config::$dbConnections);

    if(null == $database)
      $database = All_Config::$dbConnections[$confToUse]['db'];

    Session::set('db', $confToUse);
    $db = Sql::getDB('Mysql');
    $db->selectDb();

    // Checks if the database concerned exists
    $schemaInfo = $db->valuesOneCol($db->query('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA'));
    if(!in_array($database, $schemaInfo))
    {
      echo 'This database doesn\'t exist.';
      return false;
    }

    $content = '';
    foreach($db->valuesOneCol($db->query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = \'' . $database . '\'')) as $table)
    {
      $content .= $table . ': ' . PHP_EOL;
      $cols = $db->values($db->query('SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = \'' . $database . '\' AND TABLE_NAME = \'' . $table . '\''));

      if(0 < count($cols))
        $content .= '  columns:' . PHP_EOL;

      foreach($cols as $col)
      {
        $content .= '    '  . $col['COLUMN_NAME'] . ':' . PHP_EOL;
        $content .= '      type: '  . $col['COLUMN_TYPE'] . PHP_EOL;

        if('NO' == $col['IS_NULLABLE'])
          $content .= '      notnull: true' . PHP_EOL;

        if(false !== strpos($col['EXTRA'], 'auto_increment'))
          $content .= '      auto_increment: true' . PHP_EOL;

        if('PRI' == $col['COLUMN_KEY'])
          $content .= '      primary: true' . PHP_EOL;
      }

      $constraints = $db->values($db->query('SELECT REFERENCED_TABLE_NAME, COLUMN_NAME, REFERENCED_COLUMN_NAME, CONSTRAINT_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = \'' . $database . '\'
          AND TABLE_NAME = \'' . $table . '\'
          AND CONSTRAINT_NAME <> \'PRIMARY\''));

      if(0 < count($constraints))
      {
        $content .= '  relations:' . PHP_EOL;

        foreach($constraints as $constraint)
        {
          if(!isset($constraint['REFERENCED_TABLE_NAME']))
            echo 'There is no REFERENCED_TABLE_NAME on ' . (isset($constraint['CONSTRAINT_NAME']) ? $constraint['CONSTRAINT_NAME'] : '/NO CONSTRAINT NAME/');

          $content .= '    ' . $constraint['REFERENCED_TABLE_NAME'] . ':' . PHP_EOL;
          $content .= '      local: ' . $constraint['COLUMN_NAME'] . PHP_EOL;
          $content .= '      foreign: ' . $constraint['REFERENCED_COLUMN_NAME'] . PHP_EOL;
          $content .= '      constraint_name: ' . $constraint['CONSTRAINT_NAME'] . PHP_EOL;
        }
      }

      $content .= PHP_EOL;
    }

    $fp = fopen(self::$schemaFile, 'w' );
    fwrite($fp, $content);
    fclose($fp);
  }
}
?>
