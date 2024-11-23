<?php
/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);
namespace otra\console\architecture\createModel\allModelsFromYmlSchema;

use const otra\cache\php\DIR_SEPARATOR;
use const otra\console\{CLI_WARNING,END_COLOR};
use const otra\console\architecture\createModel\{CREATE_MODEL_FOLDER, MODEL_LOCATION_BUNDLE, MODULE_NAME};
use const otra\console\constants\DOUBLE_ERASE_SEQUENCE;
use function otra\console\architecture\createModel\{getModelLocation,getModuleName};

/** @var string $bundleName */
/** @var string $bundlePath */
/** @var string $modelName */
/** @var string $moduleName */
require CREATE_MODEL_FOLDER . '/allModelsFromYmlSchema/common.php';

echo CREATE_ALL_MODELS_FROM_YAML_SCHEMA;

$modelLocation = getModelLocation();

// We clean the bundle/module question
echo DOUBLE_ERASE_SEQUENCE;

if (MODEL_LOCATION_BUNDLE === $modelLocation)
{
  // we update the message
  echo CREATING_ALL_MODELS_FOR_BUNDLE;
  define('otra\console\architecture\createModel\MODEL_PATH', $bundlePath);
} else
{
  /** MODULE */
  define('otra\console\architecture\createModel\MODULE_NAME', getModuleName($bundlePath));

  echo DOUBLE_ERASE_SEQUENCE, 'A model in the bundle ', CLI_WARNING, $bundleName, END_COLOR, ' for the module ',
    CLI_WARNING, $moduleName, END_COLOR, ' ...', PHP_EOL;

  define('otra\console\architecture\createModel\MODEL_PATH', $bundlePath . MODULE_NAME . DIR_SEPARATOR);

  // We clean the module name question
  echo DOUBLE_ERASE_SEQUENCE, 'Creating all the models for the bundle ', CLI_WARNING, $bundleName, END_COLOR, ' in the module ',
    CLI_WARNING, MODULE_NAME, END_COLOR, ' ...', PHP_EOL;
}

modelsCreation($modelLocation, $bundleName, $modelName . '.php');
