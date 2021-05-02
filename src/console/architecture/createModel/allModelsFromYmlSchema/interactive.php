<?php
/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);
namespace otra\console\architecture\createModel\allModelsFromYmlSchema;

use function otra\console\architecture\createModel\{getModelLocation,getModuleName};
use const otra\console\architecture\createModel\{CREATE_MODEL_FOLDER,MODEL_LOCATION_BUNDLE};
use const otra\console\{CLI_WARNING,END_COLOR};

/** @var string $bundleName */
/** @var string $bundlePath */
/** @var string $modelName */
/** @var string $moduleName */
require CREATE_MODEL_FOLDER . '/allModelsFromYmlSchema/common.php';

echo CREATE_ALL_MODELS_FROM_YAML_SCHEMA;

$modelLocation = getModelLocation();

// We cleans the bundle/module question
echo DOUBLE_ERASE_SEQUENCE;

if (MODEL_LOCATION_BUNDLE === $modelLocation)
{
  // we update the message
  echo CREATING_ALL_MODELS_FOR_BUNDLE;
  define('MODEL_PATH', $bundlePath);
} else
{
  /** MODULE */
  define('MODULE_NAME', getModuleName($bundlePath));

  echo DOUBLE_ERASE_SEQUENCE, 'A model in the bundle ', CLI_WARNING, $bundleName, END_COLOR, ' for the module ',
    CLI_WARNING, $moduleName, END_COLOR, ' ...', PHP_EOL;

  define('MODEL_PATH', $bundlePath . MODULE_NAME . '/');

  // We cleans the module name question
  echo DOUBLE_ERASE_SEQUENCE, 'Creating all the models for the bundle ', CLI_WARNING, $bundleName, END_COLOR, ' in the module ',
    CLI_WARNING, MODULE_NAME, END_COLOR, ' ...', PHP_EOL;
}

modelsCreation($modelLocation, $bundleName, $modelName . '.php');

