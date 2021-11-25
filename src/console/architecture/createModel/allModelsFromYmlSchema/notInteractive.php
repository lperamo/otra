<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture\createModel\allModelsFromYmlSchema;

use const otra\cache\php\DIR_SEPARATOR;
use const otra\console\architecture\createModel\
{CREATE_MODEL_FOLDER, MODEL_LOCATION_BUNDLE, MODULE_NAME};
use const otra\console\{CLI_INFO_HIGHLIGHT, END_COLOR};

/** @var string $bundleName */
/** @var string $bundlePath */
/** @var int    $modelLocation */
/** @var string $modelName */
require CREATE_MODEL_FOLDER . '/allModelsFromYmlSchema/common.php';

echo CREATE_ALL_MODELS_FROM_YAML_SCHEMA;

if (MODEL_LOCATION_BUNDLE === $modelLocation)
{
  echo CREATING_ALL_MODELS_FOR_BUNDLE;
  defineModelPath($modelLocation, $bundlePath, $bundleName);
} else
{
  /** MODULE */
  echo 'A model in the bundle ', CLI_INFO_HIGHLIGHT, $bundleName, END_COLOR, ' for the module ', CLI_INFO_HIGHLIGHT,
    MODULE_NAME, END_COLOR, ' ...', PHP_EOL;
  define('otra\console\architecture\createModel\MODEL_PATH', $bundlePath . MODULE_NAME . DIR_SEPARATOR);

  // We clean the last sentence
  echo 'Creating all the models for the bundle ', CLI_INFO_HIGHLIGHT, $bundleName, END_COLOR, ' in the module ',
    CLI_INFO_HIGHLIGHT, MODULE_NAME, END_COLOR, ' ...', PHP_EOL;
}

modelsCreation($modelLocation, $bundleName, $modelName . '.php');
