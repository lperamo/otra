<?php
/**
 * @author Lionel Péramo
 * @package otra\console\database
 */
declare(strict_types=1);
namespace otra\console\database\sqlImportSchema;

use Exception;
use otra\console\database\Database;
use otra\OtraException;

const
  SQL_IMPORT_SCHEMA_ARG_DATABASE_NAME = 2,
  SQL_IMPORT_SCHEMA_ARG_CONFIGURATION = 3;

/**
 * Creates the database schema from a database.
 *
 * @param array<int, string> $argumentsVector Command-line arguments, similar to those provided by $argv.
 *
 * @throws OtraException If we cannot create the folder that will contain the schema
 * @return void
 */
function sqlImportSchema(array $argumentsVector) : void
{
  if (isset($argumentsVector[SQL_IMPORT_SCHEMA_ARG_DATABASE_NAME]))
  {
    $databaseName = $argumentsVector[SQL_IMPORT_SCHEMA_ARG_DATABASE_NAME];
    $confToUse = $argumentsVector[SQL_IMPORT_SCHEMA_ARG_CONFIGURATION] ?? null;
  }
  else
  {
    $databaseName = null;
    $confToUse = null;
  }

  if (!Database::$init)
    Database::init();

  $database = Database::_initImports($databaseName, $confToUse);
  $content = '';
  /** @var string[] $tables */
  $tables = $database->valuesOneCol($database->query(
    'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = \'' . $databaseName . '\''
  ));
  sort($tables);

  foreach ($tables as $arrayIndex => $table)
  {
    $content .= $table . ':' . PHP_EOL;
    /** @var array<int, array<string, ?string>> $columns */
    $columns = $database->values($database->query('
        SELECT `COLUMN_NAME`, `COLUMN_COMMENT`, `DATA_TYPE`, `CHARACTER_MAXIMUM_LENGTH`, `IS_NULLABLE`, `EXTRA`,
          `COLUMN_KEY`, IF(COLUMN_TYPE LIKE \'%unsigned\', \'YES\', \'NO\') as IS_UNSIGNED
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = \'' . $databaseName . '\'
         AND TABLE_NAME = \'' . $table .
      '\' ORDER BY ORDINAL_POSITION')
    );

    // If there are columns ...
    if (0 < count($columns))
      $content .= '  columns:' . PHP_EOL;

    // For each column in this table, we set the different properties
    foreach ($columns as $column)
    {
      $content .= '    ' . $column['COLUMN_NAME'] . ':' . PHP_EOL;
      $content .= '      type: ' . $column['DATA_TYPE'];

      if (isset($column['CHARACTER_MAXIMUM_LENGTH']))
        $content .= '(' . $column['CHARACTER_MAXIMUM_LENGTH'] . ')';

      if ($column['IS_UNSIGNED'] === 'YES')
        $content .= ' unsigned';

      $content .= PHP_EOL;

      if ('NO' === $column['IS_NULLABLE'])
        $content .= '      notnull: true' . PHP_EOL;

      if (str_contains($column['EXTRA'], 'auto_increment'))
        $content .= '      auto_increment: true' . PHP_EOL;

      if ('PRI' === $column['COLUMN_KEY'])
        $content .= '      primary: true' . PHP_EOL;

      if ('' !== $column['COLUMN_COMMENT'])
        $content .= '      comment: \'' . $column['COLUMN_COMMENT'] . '\'' . PHP_EOL;
    }

    /** @var array<int, array<string, string>> $constraints */
    $constraints = $database->values($database->query(
      'SELECT kcu.REFERENCED_TABLE_NAME,
            kcu.COLUMN_NAME,
            kcu.REFERENCED_COLUMN_NAME,
            kcu.CONSTRAINT_NAME,
            rc.DELETE_RULE,
            rc.UPDATE_RULE
          FROM information_schema.KEY_COLUMN_USAGE AS kcu
          INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS AS rc ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
          WHERE kcu.TABLE_SCHEMA = \'' . $databaseName . '\' 
          AND kcu.TABLE_NAME = \'' . $table . '\' 
          AND kcu.CONSTRAINT_NAME <> \'PRIMARY\''
    ));

    $hasConstraints = 0 < count($constraints);

    $constraintsForIndexes = [];

    if ($hasConstraints)
    {
      foreach ($constraints as $constraint)
      {
        $constraintsForIndexes[]= $constraint['CONSTRAINT_NAME'];
      }

      unset($constraint);
    }

    $queryNotIn = '';

    if ($constraintsForIndexes !== [])
      $queryNotIn = ' AND INDEX_NAME NOT IN (\'' . implode('\',\'', $constraintsForIndexes) . '\', \'PRIMARY\')';

    $indexesResult = $database->values($database->query(
      'SELECT NON_UNIQUE, INDEX_NAME, COLUMN_NAME
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = \'' . $databaseName . '\'
        AND TABLE_NAME = \'' . $table . '\'
        AND INDEX_NAME != \'PRIMARY\'' .
      $queryNotIn .
      'ORDER BY INDEX_NAME, SEQ_IN_INDEX;'
    ));

    if ($indexesResult !== [])
    {
      $oldIndexName = '';

      foreach ($indexesResult as $indexResult)
      {
        $indexName = $indexResult['INDEX_NAME'];

        // It's another index
        if ($indexName !== $oldIndexName)
        {
          // It's not the beginning of the parsing
          if ($oldIndexName === '')
            $content .= Database::INDENTATION . 'indexes:' . PHP_EOL;

          $oldIndexName = $indexName;
          $content .= Database::DOUBLE_INDENTATION . $indexName . ':' . PHP_EOL;

          if ($indexResult['NON_UNIQUE'] === 0)
            $content .= Database::DOUBLE_INDENTATION . Database::INDENTATION . 'category: unique' . PHP_EOL;
          $content .=
            Database::DOUBLE_INDENTATION . Database::INDENTATION . 'columns:' . PHP_EOL .
            Database::DOUBLE_INDENTATION . Database::DOUBLE_INDENTATION . '- ' . $indexResult['COLUMN_NAME'] . PHP_EOL;
        }
        else
          $content .=  Database::DOUBLE_INDENTATION . Database::DOUBLE_INDENTATION . '- ' . $indexResult['COLUMN_NAME'] . PHP_EOL;
      }
    }

    // if there are constraints for this table
    if ($hasConstraints)
    {
      $content .= '  relations:' . PHP_EOL;

      // For each constraint of this table
      foreach ($constraints as $constraint)
      {
        if (!isset($constraint['REFERENCED_TABLE_NAME']))
          echo 'There is no REFERENCED_TABLE_NAME on ' .
            ($constraint['CONSTRAINT_NAME'] ?? '/NO CONSTRAINT NAME/') . '.' . PHP_EOL;

        $content .= '    ' . $constraint['CONSTRAINT_NAME'] . ':' . PHP_EOL;
        $content .= '      foreign: ' . $constraint['REFERENCED_COLUMN_NAME'] . PHP_EOL;
        $content .= '      local: ' . $constraint['COLUMN_NAME'];

        if ($constraint['DELETE_RULE'] !== 'NO ACTION')
          $content .= PHP_EOL . '      on_delete: ' . strtolower($constraint['DELETE_RULE']);

        if ($constraint['UPDATE_RULE'] !== 'NO ACTION')
          $content .= PHP_EOL . '      on_update: ' . strtolower($constraint['UPDATE_RULE']);

        $content .= PHP_EOL . '      table: ' . $constraint['REFERENCED_TABLE_NAME'];
        $content .= PHP_EOL;
      }
    }

    // Avoids having 2 PHP_EOL at the end of the file (we put only one of it)
    if ($arrayIndex !== array_key_last($tables))
      $content .= PHP_EOL;
  }

  $saveFolder = dirname(Database::$schemaFile);

  if (!file_exists($saveFolder))
  {
    $exceptionMessage = Database::ERROR_CANNOT_REMOVE_THE_FOLDER_SLASH . $saveFolder . '\'.';

    try
    {
      if (!mkdir($saveFolder, 0777, true))
        throw new OtraException($exceptionMessage, E_CORE_ERROR);
    } catch(Exception $exception)
    {
      throw new OtraException(
        Database::ERROR_CLOSE_DIR_FORGOT_CALL .
        $exceptionMessage,
        $exception->getCode()
      );
    }
  }

  file_put_contents(Database::$schemaFile, $content);
}
