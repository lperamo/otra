<?php
declare(strict_types=1);
/** @var string $bundleName */
/** @var string $bundlePath */
/** @var int    $modelLocation */
/** @var string $modelFullName */
/** @var string $modelName */

require CREATE_MODEL_FOLDER . 'oneModelFromNothing/common.php';
echo MODEL_CREATED_FROM_NOTHING_MESSAGE;

if (MODEL_LOCATION_BUNDLE === $modelLocation)
  bundleModelPreparation($bundleName, $bundlePath);
else
{
  /** MODULE */
  define('MODEL_PATH', $bundlePath . MODULE_NAME . '/');
  echo MODULE_BUNDLE_MESSAGE;
}

echo MODEL_NAME_CREATED_FROM_NOTHING_MESSAGE;
$functions = $propertiesCode = '';

define(
  'MODEL_PROPERTIES_ARRAY',
  explode(',', MODEL_PROPERTIES)
);
define(
  'MODEL_PROPERTIES_TYPES_ARRAY',
  explode(',', MODEL_PROPERTIES_TYPES)
);
$fakeTypeArray = array_fill(0, count(MODEL_PROPERTIES_TYPES_ARRAY), 'type');

function assignType(string $key, string $value) : array
{
  return [$value => MODEL_PROPERTIES_TYPES_ARRAY[$key]];
}

$typesArray = array_map('assignType', array_keys($fakeTypeArray), $fakeTypeArray);
define('MODEL_COLUMNS_ARRAY', array_combine(MODEL_PROPERTIES_ARRAY, $typesArray));
retrieveFunctionsAndProperties(MODEL_COLUMNS_ARRAY, $modelName, $functions, $propertiesCode, $propertiesTxt);
endingTask($modelLocation, $modelName, $modelFullName, $propertiesCode, $functions, $bundleName);

