<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture\createModel;

use otra\OtraException;
use Symfony\Component\Yaml\Yaml;
use const otra\cache\php\{BUNDLES_PATH, DIR_SEPARATOR};
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT, CLI_WARNING, END_COLOR};
use const otra\console\architecture\constants\{ARG_BUNDLE_NAME, ARG_MODULE_NAME};
use const otra\console\architecture\createModel\{ARG_MODEL_NAME};

/**
 * @param string $constantName
 * @param string $message
 * @param mixed  $defaultValue
 * @param bool   $exit
 *
 * @throws OtraException
 * @return mixed
 */
$checkParameter = function (
  string $constantName,
  string $message,
  mixed $defaultValue = null,
  bool $exit = true
) use (&$argumentsVector) : mixed
{
  if (isset($argumentsVector[constant($constantName)]))
    return $argumentsVector[constant($constantName)];

  echo $exit ? CLI_ERROR : CLI_WARNING, 'You did not specified the ' . $message, END_COLOR, PHP_EOL;

  if ($exit)
    throw new OtraException(code: 1, exit: true);

  return $defaultValue;
};

$bundleName = $checkParameter(
  'otra\console\architecture\constants\ARG_BUNDLE_NAME',
  'name of the bundle.'
);

$modelLocation = (int) $checkParameter(
  __NAMESPACE__ . '\\ARG_MODEL_LOCATION',
  'location of the bundle : ' . CLI_INFO_HIGHLIGHT . 'bundle' . CLI_WARNING . ' location chosen.',
  'bundle',
  false
);

define(
  __NAMESPACE__ . '\\MODULE_NAME',
  $checkParameter('otra\console\architecture\constants\ARG_MODULE_NAME', 'module name.')
);

if (!file_exists(BUNDLES_PATH . ucfirst($bundleName)))
{
  echo CLI_ERROR, 'The bundle ', CLI_INFO_HIGHLIGHT, $bundleName, CLI_ERROR, ' does not exist !', END_COLOR, PHP_EOL;
  throw new OtraException(code: 1, exit: true);
}

$modelName = $checkParameter(
  __NAMESPACE__ . '\\ARG_MODEL_NAME',
  'name of the model. We will import all the models.',
  null,
  false
);

// We add the chosen bundle name to the path
$bundlePath = BUNDLES_PATH . ucfirst($bundleName) . DIR_SEPARATOR;

if (isset($modelName))
  $modelFullName = ucfirst($modelName) . '.php';

if (null === $modelName)
  $creationMode = CREATION_MODE_ALL_MODELS;
else
{
  define(__NAMESPACE__ . '\\YML_SCHEMA_PATH', 'config/data/yml/schema.yml');
  define(__NAMESPACE__ . '\\YML_SCHEMA_REAL_PATH', realpath($bundlePath . YML_SCHEMA_PATH));

  if (!YML_SCHEMA_REAL_PATH)
  {
    echo CLI_WARNING . 'The YAML schema does not exist so we will create a model from the console parameters.',
      END_COLOR, PHP_EOL;
    $creationMode = CREATION_MODE_FROM_NOTHING;
  } else
  {
    define(__NAMESPACE__ . '\\SCHEMA_DATA', Yaml::parse(file_get_contents(YML_SCHEMA_REAL_PATH)));

    $creationMode = (array_key_exists($modelName, SCHEMA_DATA))
      ? CREATION_MODE_ONE_MODEL
      : CREATION_MODE_FROM_NOTHING;
  }
}

if ($creationMode === CREATION_MODE_FROM_NOTHING)
{
  define(
    __NAMESPACE__ . '\\MODEL_PROPERTIES',
    $checkParameter(__NAMESPACE__ . '\\ARG_MODEL_PROPERTIES', 'model properties.')
  );
  define(
    __NAMESPACE__ . '\\MODEL_PROPERTIES_TYPES',
    $checkParameter(
      __NAMESPACE__ . '\\ARG_MODEL_PROPERTIES_TYPE',
      'model properties types.'
    )
  );
}

unset($checkParameter);
