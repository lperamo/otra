<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */

/** @var string $bundleName */
/** @var string $modelName */
require CREATE_MODEL_FOLDER . 'common.php';
define(
  'MODEL_CREATED_FROM_YAML_SCHEMA',
  'We will create one model from ' . CLI_INFO_HIGHLIGHT . DEFAULT_BDD_SCHEMA_NAME . END_COLOR . '.' . PHP_EOL
);

/**
 * @param int    $modelLocation
 * @param string $bundlePath
 * @param string $moduleName
 */
function defineModelPath(int $modelLocation, string $bundlePath, string $moduleName) : void
{
  if (!defined('MODEL_PATH'))
    define('MODEL_PATH', $bundlePath . ($modelLocation === MODEL_LOCATION_MODULE ? $moduleName . '/' : ''));
}

/**
 * @param string $modelName
 *
 * @return array
 */
function preparingBidule(string $modelName) : array
{
  $modelFullName = $modelName . '.php';
  define('AVAILABLE_TABLES', array_keys(SCHEMA_DATA));

  return [
    $modelFullName,
    ...checksModelAndTableExistence($modelFullName, $modelName)
  ];
}

/**
 * @param string $modelFullName
 * @param string $modelName
 *
 * @return array $modelExists, $tableExists
 */
#[\JetBrains\PhpStorm\ArrayShape([
  'bool',
  'bool'
])]
function checksModelAndTableExistence(string $modelFullName, string $modelName) : array
{
  return [
    file_exists(MODEL_PATH . MODEL_DIRECTORY . $modelFullName),
    in_array($modelName, AVAILABLE_TABLES, true)
  ];
}

/**
 * @param bool   $modelExists
 * @param bool   $tableExists
 * @param string $modelName
 * @param string $errorLabel
 */
function preparingErrorMessage(bool $modelExists, bool $tableExists, string $modelName, string &$errorLabel) : void
{
  if ($modelExists)
    $errorLabel .= 'This model \'' . $modelName . '\' already exists. ';

  if (!$tableExists)
    $errorLabel .= 'The schema does not contains this table ' . $modelName . ' (maybe ... check the case).';
}

