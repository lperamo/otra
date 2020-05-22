<?php
declare(strict_types=1);
require CREATE_MODEL_FOLDER . 'oneModelFromYmlSchema/common.php';
$functions = $propertiesCode = '';
echo MODEL_CREATED_FROM_YAML_SCHEMA;

if (MODEL_LOCATION_BUNDLE === $modelLocation)
  define('MODEL_PATH', $bundlePath);
else
{
  /** MODULE */
  echo MODULE_BUNDLE_MESSAGE;
  defineModelPath($modelLocation, $bundlePath, $bundleName);
}

list($modelFullName, $modelExists, $tableExists) = preparingBidule($modelName);

// If the model exists, we ask once more until we are satisfied with the user answer (we can't override it as of now)
if (true === $modelExists || false === $tableExists)
{
  $errorLabel = '';
  preparingErrorMessage($modelExists, $tableExists, $bundleName, $errorLabel);

  echo CLI_RED, $errorLabel, END_COLOR, PHP_EOL;
  throw new \otra\OtraException('', 1, '', NULL, [], true);
}

modelCreation($modelLocation, $modelName, $modelFullName, $bundleName);

