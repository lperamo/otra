<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture\createModel\oneModelFromYmlSchema;

use JetBrains\PhpStorm\ArrayShape;
use const otra\cache\php\DIR_SEPARATOR;
use const otra\console\{CLI_INFO_HIGHLIGHT, END_COLOR};
use const otra\console\architecture\createModel\
{CREATE_MODEL_FOLDER, DEFAULT_BDD_SCHEMA_NAME, MODEL_DIRECTORY, MODEL_LOCATION_MODULE, MODEL_PATH, SCHEMA_DATA};


/** @var string $bundleName The bundle in which the model have to be created */
/** @var string $modelName */
require CREATE_MODEL_FOLDER . 'common.php';
const MODEL_CREATED_FROM_YAML_SCHEMA = 'We will create one model from ' . CLI_INFO_HIGHLIGHT . DEFAULT_BDD_SCHEMA_NAME . END_COLOR . '.' . PHP_EOL;

/**
 * @param int $modelLocation Location of the model to create
 *                           0 => in the bundle (default) folder
 *                           1 => in the module folder.
 * @param string $moduleName Name of the module in which the model have to be created.
 */
function defineModelPath(int $modelLocation, string $bundlePath, string $moduleName) : void
{
  if (!defined('otra\console\architecture\createModel\MODEL_PATH'))
    define('otra\console\architecture\createModel\MODEL_PATH', $bundlePath . ($modelLocation === MODEL_LOCATION_MODULE ? $moduleName . DIR_SEPARATOR : ''));
}

/**
 * @return array{0:string, 1:bool, 2:bool}
 */
function preparingBidule(string $modelName) : array
{
  $modelFullName = $modelName . '.php';
  define(__NAMESPACE__ . '\\AVAILABLE_TABLES', array_keys(SCHEMA_DATA));

  return [
    $modelFullName,
    ...checksModelAndTableExistence($modelFullName, $modelName)
  ];
}

/**
 *
 * @return array{0:bool, 1:bool} $modelExists, $tableExists
 */
#[ArrayShape([
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

function preparingErrorMessage(bool $modelExists, bool $tableExists, string $modelName, string &$errorLabel) : void
{
  if ($modelExists)
    $errorLabel .= 'This model \'' . $modelName . '\' already exists. ';

  if (!$tableExists)
    $errorLabel .= 'The schema does not contains this table ' . $modelName . ' (maybe ... check the case).';
}
