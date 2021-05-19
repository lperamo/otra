<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture\createModel\oneModelFromNothing;

use const otra\cache\php\DIR_SEPARATOR;
use const otra\console\architecture\createModel\
{CREATE_MODEL_FOLDER, MODEL_LOCATION_BUNDLE, MODEL_PROPERTIES, MODEL_PROPERTIES_TYPES, MODULE_NAME};
use function otra\console\architecture\createModel\retrieveFunctionsAndProperties;

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
  define(
    'otra\console\architecture\createModel\MODEL_PATH',
    $bundlePath . MODULE_NAME . DIR_SEPARATOR
  );
  echo MODULE_BUNDLE_MESSAGE;
}

echo MODEL_NAME_CREATED_FROM_NOTHING_MESSAGE;
$functions = $propertiesCode = '';

define(
  'otra\console\architecture\createModel\oneModelFromNothing\MODEL_PROPERTIES_ARRAY',
  explode(',', MODEL_PROPERTIES)
);
define(
  'otra\console\architecture\createModel\oneModelFromNothing\MODEL_PROPERTIES_TYPES_ARRAY',
  explode(',', MODEL_PROPERTIES_TYPES)
);
$fakeTypeArray = array_fill(0, count(MODEL_PROPERTIES_TYPES_ARRAY), 'type');

function assignType(string $key, string $value) : array
{
  return [$value => MODEL_PROPERTIES_TYPES_ARRAY[$key]];
}

$typesArray = array_map(
  'otra\console\architecture\createModel\oneModelFromNothing\assignType',
  array_keys($fakeTypeArray),
  $fakeTypeArray
);
define(
  'otra\console\architecture\createModel\oneModelFromNothing\MODEL_COLUMNS_ARRAY',
  array_combine(MODEL_PROPERTIES_ARRAY, $typesArray)
);
retrieveFunctionsAndProperties(
  MODEL_COLUMNS_ARRAY,
  $modelName,
  $functions,
  $propertiesCode,
  $propertiesTxt
);
endingTask($modelLocation, $modelName, $modelFullName, $propertiesCode, $functions, $bundleName);
