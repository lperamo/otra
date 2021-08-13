<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture\createModel\oneModelFromYmlSchema;

use otra\OtraException;
use function otra\console\architecture\createModel\modelCreation;
use const otra\console\architecture\createModel\{CREATE_MODEL_FOLDER, MODEL_LOCATION_BUNDLE, MODULE_BUNDLE_MESSAGE};
use const otra\console\{CLI_ERROR, END_COLOR};

/** @var string $bundleName */
/** @var string $bundlePath */
/** @var int    $modelLocation */
/** @var string $modelName */

require CREATE_MODEL_FOLDER . 'oneModelFromYmlSchema/common.php';
$functions = $propertiesCode = '';
echo MODEL_CREATED_FROM_YAML_SCHEMA;

if (MODEL_LOCATION_BUNDLE === $modelLocation)
  define('otra\console\architecture\createModel\MODEL_PATH', $bundlePath);
else
{
  /** MODULE */
  echo MODULE_BUNDLE_MESSAGE;
  defineModelPath($modelLocation, $bundlePath, $bundleName);
}

[$modelFullName, $modelExists, $tableExists] = preparingBidule($modelName);

// If the model exists, we ask once more until we are satisfied with the user answer (we can't override it as of now)
if (true === $modelExists || false === $tableExists)
{
  $errorLabel = '';
  preparingErrorMessage($modelExists, $tableExists, $bundleName, $errorLabel);

  echo CLI_ERROR, $errorLabel, END_COLOR, PHP_EOL;
  throw new OtraException(code: 1, exit: true);
}

modelCreation($modelLocation, $modelName, $modelFullName, $bundleName);
