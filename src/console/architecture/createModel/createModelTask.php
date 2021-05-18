<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture\constants
{
  const
    ARG_BUNDLE_NAME = 2,
    ARG_INTERACTIVE = 4,
    ARG_MODULE_NAME = 6;
}

namespace otra\console\architecture\createModel
{
  use otra\OtraException;
  use Symfony\Component\Yaml\Yaml;
  use const otra\cache\php\{CONSOLE_PATH, DIR_SEPARATOR, SPACE_INDENT};
  use const otra\console\
  {architecture\constants\ARG_INTERACTIVE, CLI_ERROR, CLI_INFO_HIGHLIGHT, CLI_TABLE, END_COLOR};
  use function otra\console\architecture\checkBooleanArgument;

  // Testing interactive argument
  require CONSOLE_PATH . 'architecture/checkBooleanArgument.php';
  $interactive = checkBooleanArgument($argv, ARG_INTERACTIVE, 'interactive');

  // Other task arguments
  const ARG_METHOD = 3,
    ARG_MODEL_LOCATION = 5,
    ARG_MODEL_NAME = 7,
    ARG_MODEL_PROPERTIES = 8,
    ARG_MODEL_PROPERTIES_TYPE = 9,

    // Creation modes
    CREATION_MODE_FROM_NOTHING = 1,
    CREATION_MODE_ONE_MODEL = 2,
    CREATION_MODE_ALL_MODELS = 3,

    // Model locations
    MODEL_LOCATION_BUNDLE = 0,
    MODEL_LOCATION_MODULE = 1,

    // Paths
    DEFAULT_BDD_SCHEMA_NAME = 'schema.yml',
    MODEL_DIRECTORY = 'models/',
    CREATE_MODEL_FOLDER = CONSOLE_PATH . 'architecture/createModel/',

  // String in file name
  OTRA_NTERACTIVE = 'nteractive.php';

  // Loading common functions
  require CONSOLE_PATH . 'tools.php';
  require CREATE_MODEL_FOLDER . 'createModel.php';

  /**
   * @var string $modelLocation
   * @var string $modelName
   */
  // Checking parameters...
  /**
   * @var string $bundleName
   * @var string $bundlePath
   * @var int    $creationMode
   */
  define(
    'otra\console\architecture\createModel\INTERACTIVE_FILE_NAME',
    ($interactive ? 'i' : 'notI') . OTRA_NTERACTIVE
  );
  require CREATE_MODEL_FOLDER . 'checkParameters/' . INTERACTIVE_FILE_NAME;

  echo 'We use the ', CLI_INFO_HIGHLIGHT, $bundleName, END_COLOR, ' bundle.', PHP_EOL;

  // Code creation...
  const FUNCTION_START = SPACE_INDENT . 'public function ';

  if (CREATION_MODE_FROM_NOTHING === $creationMode)
    require CREATE_MODEL_FOLDER . 'oneModelFromNothing/' . INTERACTIVE_FILE_NAME;
  else
  {
    if (!defined('otra\console\architecture\createModel\YML_SCHEMA_PATH'))
    {
      define('otra\console\architecture\createModel\YML_SCHEMA_PATH', 'config/data/yml/schema.yml');
      define('otra\console\architecture\createModel\YML_SCHEMA_REAL_PATH', realpath($bundlePath . YML_SCHEMA_PATH));
    }

    if (!YML_SCHEMA_REAL_PATH)
    {
      echo CLI_ERROR, 'The YAML schema ', CLI_TABLE, 'BASE_PATH + ', CLI_INFO_HIGHLIGHT, 'bundles/', ucfirst($bundleName),
        DIR_SEPARATOR . YML_SCHEMA_PATH, CLI_ERROR, ' does not exist.', END_COLOR, PHP_EOL;
      throw new OtraException('', 1, '', null, [], true);
    }

    if (!defined('otra\console\architecture\createModel\SCHEMA_DATA'))
      define(
        'otra\console\architecture\createModel\SCHEMA_DATA',
        Yaml::parse(file_get_contents(YML_SCHEMA_REAL_PATH))
      );

    if (SCHEMA_DATA === null)
    {
      echo CLI_ERROR, 'The schema ', CLI_TABLE, 'BASE_PATH + ', CLI_INFO_HIGHLIGHT, 'bundles/', ucfirst($bundleName),
      YML_SCHEMA_PATH, CLI_ERROR, ' is empty !', END_COLOR, PHP_EOL;
      throw new OtraException('', 1, '', null, [], true);
    }

    require CREATE_MODEL_FOLDER .
      (CREATION_MODE_ONE_MODEL === $creationMode
        ? 'oneModelFromYmlSchema/'
        : 'allModelsFromYmlSchema/'
      ) . INTERACTIVE_FILE_NAME;
  }
}
