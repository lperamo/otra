<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */

declare(strict_types=1);

namespace otra\console\architecture\createModel\oneModelFromYmlSchema;

use function otra\console\architecture\createModel\{getModelLocation,getModuleName,modelCreation};
use function otra\console\promptUser;
use const otra\console\architecture\createModel\{CREATE_MODEL_FOLDER,MODEL_LOCATION_BUNDLE};

/** @var string $bundleName */
/** @var string $bundlePath */
require CREATE_MODEL_FOLDER . 'oneModelFromYmlSchema/common.php';
$functions = $propertiesCode = '';
echo MODEL_CREATED_FROM_YAML_SCHEMA;
$modelNameQuestion =
  'What is the name of the model that you want to create from \'schema.yml\' ? (camelCase, no need to put .php)';

// We cleans the bundle/module question
echo DOUBLE_ERASE_SEQUENCE;
$modelLocation = getModelLocation();

if (MODEL_LOCATION_BUNDLE === $modelLocation)
  define('MODEL_PATH', $bundlePath);
else
{
  /** MODULE */
  $moduleName = getModuleName($bundlePath);
  echo MODULE_BUNDLE_MESSAGE;
  defineModelPath($modelLocation, $bundlePath, $bundleName);

  // We cleans the module name question
  echo DOUBLE_ERASE_SEQUENCE;
}

$modelName = promptUser($modelNameQuestion, 'Bad answer. ' . $modelNameQuestion);
[$modelFullName, $modelExists, $tableExists] = preparingBidule($modelName);

// If the model exists, we ask once more until we are satisfied with the user answer (we can't override it as of now)
while (true === $modelExists || false === $tableExists)
{
  echo DOUBLE_ERASE_SEQUENCE;
  $errorLabel = '';
  preparingErrorMessage($modelExists, $tableExists, $bundleName, $errorLabel);
  $errorLabel .= $modelNameQuestion;
  $modelName = promptUser($errorLabel, $errorLabel);
  $modelFullName = $modelName . '.php';
  [$modelExists, $tableExists] = checksModelAndTableExistence($modelFullName, $modelName);
}

// We cleans the last sentence
echo ERASE_SEQUENCE;
modelCreation($modelLocation, $modelName, $modelFullName, $bundleName);

