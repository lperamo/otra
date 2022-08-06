<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture\createModel\oneModelFromNothing;

use function otra\console\architecture\createModel\{modelCreationSuccess,writeModelFile};
use const otra\cache\php\SPACE_INDENT;
use const otra\console\architecture\createModel\{CREATE_MODEL_FOLDER, FUNCTION_START, MODEL_PATH};
use const otra\console\{CLI_INFO_HIGHLIGHT,END_COLOR};

/** @var string $modelName */
require CREATE_MODEL_FOLDER . 'common.php';
const MODEL_CREATED_FROM_NOTHING_MESSAGE = 'We will create one model from nothing.' . PHP_EOL;
define(
  __NAMESPACE__ . '\\MODEL_NAME_CREATED_FROM_NOTHING_MESSAGE',
  'The model ' . CLI_INFO_HIGHLIGHT . $modelName . END_COLOR . ' will be created from nothing...' . PHP_EOL
);

/**
 * @param string $bundleName The bundle in which the model have to be created
 */
function bundleModelPreparation(string $bundleName, string $bundlePath) : void
{
  echo 'A model for the bundle ', CLI_INFO_HIGHLIGHT, $bundleName, END_COLOR, ' ...', PHP_EOL;
  define('otra\console\architecture\createModel\MODEL_PATH', $bundlePath);
}

function addCode(string &$functions, string &$propertiesCode, string $property) : void
{
  $functions .= PHP_EOL;

  // Adds property declaration
  $propertiesCode .= SPACE_INDENT . 'protected $' . $property . ';' . PHP_EOL;

  // Adds getters and setters
  $functionEnd = ucfirst($property) . '()' . PHP_EOL . '  {' . PHP_EOL . '  }' . PHP_EOL;
  $functions .= FUNCTION_START . 'get' .  $functionEnd . PHP_EOL . FUNCTION_START . 'set' .  $functionEnd;
}

/**
 * @param int    $modelLocation Location of the model to create
 *                              0 => in the bundle (default) folder
 *                              1 => in the module folder.
 * @param string $bundleName    The bundle in which the model have to be created
 */
function endingTask(
  int $modelLocation,
  string $modelName,
  string $modelFullName,
  string $propertiesCode,
  string $functions,
  string $bundleName
) : void
{
  writeModelFile($modelLocation, $bundleName, MODEL_PATH, $modelName, $modelFullName, $propertiesCode, $functions);
  modelCreationSuccess($bundleName, $modelName);
}
