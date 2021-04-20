<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */

/** @var string $bundleName */
define(
  'MODULE_BUNDLE_MESSAGE',
  'A model in the bundle ' . CLI_INFO_HIGHLIGHT . $bundleName . END_COLOR . ' for the module ' .
  CLI_INFO_HIGHLIGHT . MODULE_NAME . END_COLOR . ' ...' . PHP_EOL
);
// This variable is used for code creation
const START_ACCOLADE = PHP_EOL . SPACE_INDENT . '{' . PHP_EOL;
/**
 * @param int    $modelLocation
 * @param string $modelName
 * @param string $modelFullName
 * @param string $bundleName
 *
 * @throws \otra\OtraException
 */
function modelCreation(int $modelLocation, string $modelName,string &$modelFullName,string $bundleName): void
{
  $modelFullName = $modelName . '.php';
  $functions = $propertiesCode = '';
  retrieveFunctionsAndProperties(
    SCHEMA_DATA[$modelName]['columns'],
    $modelName,
    $functions,
    $propertiesCode,
    $propertiesTxt
  );
  writeModelFile(
    $modelLocation,
    $bundleName,
    MODEL_PATH,
    $modelName,
    $modelFullName,
    $propertiesCode,
    $functions
  );
  modelCreationSuccess($bundleName, $modelName, $propertiesTxt);
}

