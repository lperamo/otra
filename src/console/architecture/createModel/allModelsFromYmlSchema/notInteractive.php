<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */

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
  echo 'A model in the bundle ', CLI_LIGHT_CYAN, $bundleName, END_COLOR, ' for the module ', CLI_LIGHT_CYAN,
    MODULE_NAME, END_COLOR, ' ...', PHP_EOL;
  define('MODEL_PATH', $bundlePath . MODULE_NAME . '/');

  // We cleans the last sentence
  echo 'Creating all the models for the bundle ', CLI_LIGHT_CYAN, $bundleName, END_COLOR, ' in the module ',
    CLI_LIGHT_CYAN, MODULE_NAME, END_COLOR, ' ...', PHP_EOL;
}

modelsCreation($modelLocation, $bundleName, $modelName . '.php');

