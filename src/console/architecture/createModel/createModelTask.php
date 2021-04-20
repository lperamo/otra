<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */

use otra\OtraException;

// Testing interactive argument
define('ARG_INTERACTIVE', 4);
/** @var bool $interactive */
require CONSOLE_PATH . 'architecture/checkInteractiveMode.php';

// Other task arguments
define('ARG_BUNDLE_NAME', 2);
define('ARG_METHOD', 3);
define('ARG_MODEL_LOCATION', 5);
define('ARG_MODULE_NAME', 6);
define('ARG_MODEL_NAME', 7);
define('ARG_MODEL_PROPERTIES', 8);
define('ARG_MODEL_PROPERTIES_TYPE', 9);

// Creation modes
define('CREATION_MODE_FROM_NOTHING', 1);
define('CREATION_MODE_ONE_MODEL', 2);
define('CREATION_MODE_ALL_MODELS', 3);

// Model locations
define('MODEL_LOCATION_BUNDLE', 0);
define('MODEL_LOCATION_MODULE', 1);

// Paths
define('DEFAULT_BDD_SCHEMA_NAME', 'schema.yml');
define('MODEL_DIRECTORY', 'models/');
define('CREATE_MODEL_FOLDER', CONSOLE_PATH . 'architecture/createModel/');

// String in file name
define('OTRA_NTERACTIVE', 'nteractive.php');

// Loading common functions
require CONSOLE_PATH . 'tools.php';
require CREATE_MODEL_FOLDER . 'createModel.php';

/**
 * @var int $creationMode
 * @var string $modelLocation
 * @var string $modelName
 */
// Checking parameters...
$missingBundleErrorMessage = 'This bundle does not exist ! Try once again :';
/** @var string $bundleName */
/** @var string $bundlePath */
require CREATE_MODEL_FOLDER . 'checkParameters/' . ($interactive ? 'i' : 'notI') . OTRA_NTERACTIVE;

echo 'We use the ', CLI_INFO_HIGHLIGHT, $bundleName, END_COLOR, ' bundle.', PHP_EOL;

// Code creation...
define('FUNCTION_START', SPACE_INDENT . 'public function ');

if (CREATION_MODE_FROM_NOTHING === $creationMode)
  require CREATE_MODEL_FOLDER . 'oneModelFromNothing/' . (!$interactive ? 'notI' : 'i') . OTRA_NTERACTIVE;
else
{
  if (!defined('YML_SCHEMA_PATH'))
  {
    define('YML_SCHEMA_PATH', 'config/data/yml/schema.yml');
    define('YML_SCHEMA_REAL_PATH', realpath($bundlePath . YML_SCHEMA_PATH));
  }

  if (!YML_SCHEMA_REAL_PATH)
  {
    echo CLI_ERROR, 'The YAML schema ', CLI_TABLE, 'BASE_PATH + ', CLI_INFO_HIGHLIGHT, 'bundles/', ucfirst($bundleName),
      '/' . YML_SCHEMA_PATH, CLI_ERROR, ' does not exist.', END_COLOR, PHP_EOL;
    throw new OtraException('', 1, '', NULL, [], true);
  }

  if (!defined('SCHEMA_DATA'))
    define(
      'SCHEMA_DATA',
      Symfony\Component\Yaml\Yaml::parse(file_get_contents(YML_SCHEMA_REAL_PATH))
    );

  if (SCHEMA_DATA === null)
  {
    echo CLI_ERROR, 'The schema ', CLI_TABLE, 'BASE_PATH + ', CLI_INFO_HIGHLIGHT, 'bundles/', ucfirst($bundleName),
      YML_SCHEMA_PATH, CLI_ERROR, ' is empty !', END_COLOR, PHP_EOL;
    throw new OtraException('', 1, '', NULL, [], true);
  }

  require CREATE_MODEL_FOLDER .
    (CREATION_MODE_ONE_MODEL === $creationMode
      ? 'oneModelFromYmlSchema/'
      : 'allModelsFromYmlSchema/'
    ) . (!$interactive ? 'notI' : 'i') . OTRA_NTERACTIVE;
}

