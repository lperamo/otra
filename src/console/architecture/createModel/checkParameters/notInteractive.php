<?php
declare(strict_types=1);

use otra\OtraException;

/** @var string $bundlesPath */

/**
 * @param string $constantName
 * @param string $message
 * @param null   $defaultValue
 * @param bool   $exit
 *
 * @return mixed|null
 */
$checkParameter = function (string $constantName, string $message, $defaultValue = null, $exit = true) use (&$argv)
{
  if (isset($argv[constant($constantName)]))
    return $argv[constant($constantName)];

  echo $exit ? CLI_RED : CLI_YELLOW, 'You did not specified the ' . $message, END_COLOR, PHP_EOL;

  if ($exit)
    throw new OtraException('', 1, '', NULL, [], true);

  return $defaultValue;
};

$bundleName = $checkParameter(
  'ARG_BUNDLE_NAME',
  'name of the bundle.'
);

$modelLocation = (int) $checkParameter(
  'ARG_MODEL_LOCATION',
  'location of the bundle : ' . CLI_LIGHT_CYAN . 'bundle' . CLI_YELLOW . ' location chosen.',
  'bundle',
  false
);

define('MODULE_NAME', $checkParameter('ARG_MODULE_NAME', 'module name.'));

if (!file_exists($bundlesPath . ucfirst($bundleName)))
{
  echo CLI_RED, 'The bundle ', CLI_LIGHT_CYAN, $bundleName, CLI_RED, ' does not exist !', END_COLOR, PHP_EOL;
  throw new OtraException('', 1, '', NULL, [], true);
}

$modelName = $checkParameter(
  'ARG_MODEL_NAME',
  'name of the model. We will import all the models.',
  null,
  false
);

// We add the chosen bundle name to the path
$bundlePath = $bundlesPath . ucfirst($bundleName) . '/';

if (isset($modelName))
  $modelFullName = ucfirst($modelName) . '.php';

if (null === $modelName)
  $creationMode = CREATION_MODE_ALL_MODELS;
else
{
  define('YML_SCHEMA_PATH', 'config/data/yml/schema.yml');
  define('YML_SCHEMA_REAL_PATH', realpath($bundlePath . YML_SCHEMA_PATH));

  if (!YML_SCHEMA_REAL_PATH)
  {
    echo CLI_YELLOW . 'The YAML schema does not exist so we will create a model from the console parameters.',
      END_COLOR, PHP_EOL;
    $creationMode = CREATION_MODE_FROM_NOTHING;
  } else
  {
    define(
      'SCHEMA_DATA',
      Symfony\Component\Yaml\Yaml::parse(file_get_contents(YML_SCHEMA_REAL_PATH))
    );

    $creationMode = (in_array($modelName, array_keys(SCHEMA_DATA)) === true)
      ? CREATION_MODE_ONE_MODEL
      : CREATION_MODE_FROM_NOTHING;
  }
}

if ($creationMode === CREATION_MODE_FROM_NOTHING)
{
  define(
    'MODEL_PROPERTIES',
    $checkParameter('ARG_MODEL_PROPERTIES', 'model properties.')
  );

  define(
    'MODEL_PROPERTIES_TYPES',
    $checkParameter('ARG_MODEL_PROPERTIES_TYPE', 'model properties types.')
  );
}


unset($checkParameter);

