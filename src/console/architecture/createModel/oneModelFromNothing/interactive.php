<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */

/** @var string $bundleName */
/** @var string $bundlePath */
require CREATE_MODEL_FOLDER . 'oneModelFromNothing/common.php';
echo MODEL_CREATED_FROM_NOTHING_MESSAGE;
$modelLocation = getModelLocation();
$modelNameQuestion = 'What is the name of your new model ? (camelCase, no need to put .php)';
echo DOUBLE_ERASE_SEQUENCE;

if (MODEL_LOCATION_BUNDLE === $modelLocation)
  bundleModelPreparation($bundleName, $bundlePath);
else
{
  /** MODULE */
  $moduleName = getModuleName($bundlePath);
  echo MODULE_BUNDLE_MESSAGE;
  define('MODEL_PATH', $bundlePath . $moduleName . '/');
}

$modelName = promptUser($modelNameQuestion, 'Bad answer. ' . $modelNameQuestion);
list($modelFullName, $modelExists) = getModelFullNameAndModelExists($modelName, $modelNameQuestion);
echo DOUBLE_ERASE_SEQUENCE;

while (file_exists(MODEL_PATH . MODEL_DIRECTORY . $modelFullName))
{
  $modelName = promptUser($modelExists, $modelExists);
  // We update the informations right now in order to deliver precise error messages
  list($modelFullName, $modelExists) = getModelFullNameAndModelExists($modelName, $modelNameQuestion);
  echo DOUBLE_ERASE_SEQUENCE;
}

echo MODEL_NAME_CREATED_FROM_NOTHING_MESSAGE;
$propertiesTxt = $functions = $propertiesCode = '';

define(
  'PROPERTY_TEXT',
  'Which property do you want to add ? (lowercase, type \'no!more\' if you don\'t want any other property)'
);
define('PROPERTY_ERROR_TEXT', 'You did not type anything. ' . PROPERTY_TEXT);
$property = promptUser(PROPERTY_TEXT, PROPERTY_ERROR_TEXT);

// Ask until we don't want any other properties.
while ('no!more' !== $property)
{
  addCode($functions, $propertiesCode, $property);

  // Do we want another properties ?
  $property = promptUser(DOUBLE_ERASE_SEQUENCE . PROPERTY_TEXT, PROPERTY_ERROR_TEXT);
}

endingTask($modelLocation, $modelName, $modelFullName, $propertiesCode, $functions, $bundleName);

