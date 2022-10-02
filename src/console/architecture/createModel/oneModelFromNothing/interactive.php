<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture\createModel\oneModelFromNothing;

use const otra\cache\php\DIR_SEPARATOR;
use const otra\console\architecture\createModel\
{CREATE_MODEL_FOLDER, MODEL_DIRECTORY, MODEL_LOCATION_BUNDLE, MODEL_PATH, MODULE_BUNDLE_MESSAGE};
use const otra\console\constants\DOUBLE_ERASE_SEQUENCE;
use function otra\console\architecture\createModel\{getModelFullNameAndModelExists,getModelLocation,getModuleName};
use function otra\console\promptUser;

/** @var string $bundlePath */
define('otra\console\architecture\createModel\MODULE_NAME', getModuleName($bundlePath));

/**
 * @var string $bundleName
 * @var callable bundleModelPreparation
 */
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
  define('otra\console\architecture\createModel\MODEL_PATH', $bundlePath . $moduleName . DIR_SEPARATOR);
}

$modelName = promptUser($modelNameQuestion, 'Bad answer. ' . $modelNameQuestion);
[$modelFullName, $modelExists] = getModelFullNameAndModelExists($modelName, $modelNameQuestion);
echo DOUBLE_ERASE_SEQUENCE;

while (file_exists(MODEL_PATH . MODEL_DIRECTORY . $modelFullName))
{
  $modelName = promptUser($modelExists, $modelExists);
  // We update the information right now in order to deliver precise error messages
  [$modelFullName, $modelExists] = getModelFullNameAndModelExists($modelName, $modelNameQuestion);
  echo DOUBLE_ERASE_SEQUENCE;
}

echo MODEL_NAME_CREATED_FROM_NOTHING_MESSAGE;
$propertiesTxt = $functions = $propertiesCode = '';

const PROPERTY_TEXT = 'Which property do you want to add ? (lowercase, type \'no!more\' if you don\'t want any other property)';
const PROPERTY_ERROR_TEXT = 'You did not type anything. ' . PROPERTY_TEXT;
$property = promptUser(PROPERTY_TEXT, PROPERTY_ERROR_TEXT);

// Ask until we don't want any other properties.
while ('no!more' !== $property)
{
  addCode($functions, $propertiesCode, $property);

  // Do we want another properties ?
  $property = promptUser(DOUBLE_ERASE_SEQUENCE . PROPERTY_TEXT, PROPERTY_ERROR_TEXT);
}

endingTask($modelLocation, $modelName, $modelFullName, $propertiesCode, $functions, $bundleName);
