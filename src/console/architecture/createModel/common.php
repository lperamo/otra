<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture\createModel;

use otra\OtraException;
use const otra\cache\php\SPACE_INDENT;
use const otra\console\{CLI_INFO_HIGHLIGHT,END_COLOR};
/** @var string $bundleName */

define(
  __NAMESPACE__ . '\\MODULE_BUNDLE_MESSAGE',
  'A model in the bundle ' . CLI_INFO_HIGHLIGHT . $bundleName . END_COLOR . ' for the module ' .
  CLI_INFO_HIGHLIGHT . MODULE_NAME . END_COLOR . ' ...' . PHP_EOL
);
// This variable is used for code creation
const START_ACCOLADE = PHP_EOL . SPACE_INDENT . '{' . PHP_EOL;
/**
 * @param int    $modelLocation Location of the model to create
 *                              0 => in the bundle (default) folder
 *                              1 => in the module folder.
 * @param string $bundleName    The bundle in which the model have to be created
 *
 * @throws OtraException
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
