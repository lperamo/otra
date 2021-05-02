<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture\createModel\allModelsFromYmlSchema;

use otra\OtraException;
use const otra\cache\php\DIR_SEPARATOR;
use const otra\console\architecture\createModel\
{CREATE_MODEL_FOLDER, DEFAULT_BDD_SCHEMA_NAME, MODEL_LOCATION_MODULE, SCHEMA_DATA};
use const otra\console\{CLI_INFO_HIGHLIGHT,END_COLOR};
use function otra\console\architecture\createModel\modelCreation;
/** @var string $bundleName */

require CREATE_MODEL_FOLDER . 'common.php';

const CREATE_ALL_MODELS_FROM_YAML_SCHEMA = 'We will create all the models from ' . CLI_INFO_HIGHLIGHT .
  DEFAULT_BDD_SCHEMA_NAME . END_COLOR . '.' . PHP_EOL;
define(
  'CREATING_ALL_MODELS_FOR_BUNDLE',
  'Creating all the models for the bundle ' . CLI_INFO_HIGHLIGHT . $bundleName . END_COLOR . ' ...' . PHP_EOL
);

/**
 * @param int    $modelLocation
 * @param string $bundlePath
 * @param string $moduleName
 */
function defineModelPath(int $modelLocation, string $bundlePath, string $moduleName) : void
{
  if (!defined('otra\console\architecture\createModel\MODEL_PATH'))
    define(
      'otra\console\architecture\createModel\MODEL_PATH',
      $bundlePath . ($modelLocation === MODEL_LOCATION_MODULE ? $moduleName . DIR_SEPARATOR : '')
    );
}

/**
 * @param int    $modelLocation
 * @param string $bundleName
 * @param string $modelFullName
 *
 * @throws OtraException
 */
function modelsCreation(int $modelLocation, string $bundleName, string $modelFullName): void
{
  foreach (array_keys(SCHEMA_DATA) as $modelName)
  {
    modelCreation($modelLocation, $modelName, $modelFullName, $bundleName);
  }
}

