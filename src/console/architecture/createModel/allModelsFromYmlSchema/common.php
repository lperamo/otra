<?php
require CREATE_MODEL_FOLDER . 'common.php';

define(
  'CREATE_ALL_MODELS_FROM_YAML_SCHEMA',
  'We will create all models from ' . CLI_LIGHT_CYAN . DEFAULT_BDD_SCHEMA_NAME . END_COLOR . '.' . PHP_EOL
);
define(
  'CREATING_ALL_MODELS_FOR_BUNDLE',
  'Creating all the models for the bundle ' . CLI_LIGHT_CYAN . $bundleName . END_COLOR . ' ...' . PHP_EOL
);

/**
 * @param int    $modelLocation
 * @param string $bundlePath
 * @param string $moduleName
 */
function defineModelPath(int $modelLocation, string &$bundlePath, string &$moduleName) : void
{
  if (defined('MODEL_PATH') === false)
    define('MODEL_PATH', $bundlePath . ($modelLocation === MODEL_LOCATION_MODULE ? $moduleName . '/' : ''));
}

/**
 * @param int    $modelLocation
 * @param string $bundleName
 * @param string $modelFullName
 *
 * @throws \otra\OtraException
 */
function modelsCreation(int $modelLocation, string $bundleName, string $modelFullName): void
{
  foreach (array_keys(SCHEMA_DATA) as $modelName)
  {
    modelCreation($modelLocation, $modelName, $modelFullName, $bundleName);
  }
}
?>
