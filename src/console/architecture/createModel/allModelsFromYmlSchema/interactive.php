<?php
declare(strict_types=1);
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
  define('MODULE_NAME', getModuleName($bundleName, $bundlePath));

  echo DOUBLE_ERASE_SEQUENCE, 'A model in the bundle ', CLI_YELLOW, $bundleName, END_COLOR, ' for the module ', CLI_YELLOW,
  $moduleName, END_COLOR, ' ...', PHP_EOL;

  define('MODEL_PATH', $bundlePath . MODULE_NAME . '/');

  // We cleans the module name question
  echo DOUBLE_ERASE_SEQUENCE, 'Creating all the models for the bundle ', CLI_YELLOW, $bundleName, END_COLOR, ' in the module ',
    CLI_YELLOW, MODULE_NAME, END_COLOR, ' ...', PHP_EOL;
}

modelsCreation($modelLocation, $bundleName, $modelName . '.php');

