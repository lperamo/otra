<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */

use otra\OtraException;

if (!defined('OTRA_SUCCESS'))
  define('OTRA_SUCCESS', CLI_GREEN . ' ✔' . END_COLOR . PHP_EOL);

$bundlesPath = BASE_PATH . 'bundles/';

/**
 * If we do not have the information of the type of this property in schema.yml,
 * we notice the user of that and we stop the script
 *
 * @param string $modelName
 * @param string $columnName
 * @param ?string $type
 *
 * @throws OtraException
 */
function checkDataType(string $modelName, string $columnName, ?string $type) : void
{
  if (!isset($type))
  {
    echo CLI_RED, 'SCHEMA.YML => Model ', CLI_YELLOW, $modelName, CLI_RED, ' : There are no type for the property ',
      CLI_YELLOW, $columnName, CLI_RED, '.', END_COLOR;
    throw new OtraException('', 1, '', NULL, [], true);
  }
}

/**
 * Analyzes the SQL type of the current column in the schema.yml file and gives PHP type in return
 *
 * @param string $modelName
 * @param string $columnName
 * @param string $type
 *
 * @return string
 * @throws OtraException
 */
function getDataType(string $modelName, string $columnName, string $type) : string
{
  if (str_contains($type, 'char') || str_contains($type, 'text'))
    return 'string';

  if (str_contains($type, 'int'))
    return 'int';

  if (str_contains($type, 'float') || str_contains($type, 'double'))
    return 'float';

  if (str_contains($type, 'bool'))
    return 'bool';

  if (str_contains($type, 'timestamp') || str_contains($type, 'date'))
    return 'DateTime';

  // If we don't know this type !
  echo CLI_RED, 'We don\'t know the type ', CLI_YELLOW, $type, CLI_RED, ' in ', CLI_YELLOW, $modelName, CLI_RED,
    ' for the property ', CLI_YELLOW, $columnName, CLI_RED, ' !', END_COLOR;
  throw new OtraException('', 1, '', NULL, [], true);
}

/**
 * Returns the code of the getters and setters related to this property
 *
 * @param string $columnName
 * @param string $type       Column type
 * @param string $functions  Existing creation code for functions
 */
function addGettersAndSetters(string $columnName, string $type, string &$functions) : void
{
  $ucfirstColumnName = ucfirst($columnName);
  $functionEnd = $columnName . ';' . PHP_EOL . '  }' . PHP_EOL;
  $functions .= FUNCTION_START .
    'get' . $ucfirstColumnName . '()' . ($type === '' ? '' : ' : ' . $type)  . START_ACCOLADE . SPACE_INDENT .
    SPACE_INDENT . 'return $this->' . $functionEnd . PHP_EOL .
    FUNCTION_START .
    'set' . $ucfirstColumnName . '(' . ($type === '' ? '' : $type . ' ') . '$' . $columnName . ') : void' . START_ACCOLADE . SPACE_INDENT . SPACE_INDENT . '$this->' .
    $columnName . ' = ' . '$' . $functionEnd;
}

/**
 * Shows the success sentence after the successful creation of a model.
 *
 * @param string      $bundleName
 * @param string      $modelName
 * @param string|null $propertiesTxt
 */
function modelCreationSuccess(string $bundleName, string $modelName, string $propertiesTxt = null) : void
{
  echo 'The model ', CLI_LIGHT_CYAN, $modelName, END_COLOR, ' has been created in the bundle ', CLI_LIGHT_CYAN,
    $bundleName, END_COLOR;

  if (null !== $propertiesTxt)
    echo ' with those properties [', substr($propertiesTxt, 0, strlen($propertiesTxt) - 2), ']';

  echo '.', OTRA_SUCCESS;
}

/**
 * Creates the 'models' folder if it doesn't exist and then creates the model file that we wanted.
 *
 * @param int    $modelLocation
 * @param string $bundleName
 * @param string $bundlePath
 * @param string $modelName
 * @param string $modelFullName
 * @param string $propertiesCode
 * @param string $functions
 */
function writeModelFile(
  int $modelLocation,
  string $bundleName,
  string $bundlePath,
  string $modelName,
  string $modelFullName,
  string $propertiesCode,
  string $functions) : void
{
  // If the 'models' folder doesn't exist => creates it.
  $modelsPath = $bundlePath . MODEL_DIRECTORY;

  if (!file_exists($modelsPath))
    mkdir($modelsPath, 0755, true);

  // Creates the model with all the content that we gathered.
  $filePointer = fopen($modelsPath . ucfirst($modelFullName), 'w');

  // If we use DateTime type then we add a use statement to handle this type.
  $useDateTime = '';

  if (mb_strpos($functions, 'DateTime') !== false)
    $useDateTime = PHP_EOL . 'use \DateTime;' . PHP_EOL;

  fwrite($filePointer, '<?php' . PHP_EOL .
    'namespace bundles\\' . ucfirst($bundleName) .
    ($modelLocation === MODEL_LOCATION_MODULE ? '\\' . MODULE_NAME : '') . '\\models;' . PHP_EOL .
    $useDateTime . PHP_EOL .
    'class ' . ucfirst($modelName) . PHP_EOL .
    '{' . PHP_EOL . $propertiesCode . $functions . '}' . PHP_EOL);
  fclose($filePointer);
}

/**
 * @param string $modelName
 * @param string $modelNameQuestion
 *
 * @return array [$modelFullName, $modelExists]
 */
function getModelFullNameAndModelExists(string $modelName, string $modelNameQuestion) : array
{
  return [
    $modelName . '.php',
    'This model \'' . $modelName . '\' already exists. ' . $modelNameQuestion
  ];
}

/**
 * Asks the user in order to know if he wants a BUNDLE model or a MODULE model.
 *
 * @return int 0 => bundle or 1 => model
 */
function getModelLocation() : int
{
  $modelLocation = promptUser('Is it a bundle model or a module model ? (' . MODEL_LOCATION_BUNDLE .
    ' => bundle, ' . MODEL_LOCATION_MODULE . ' => module)');

  while ('0' !== $modelLocation && '1' !== $modelLocation)
  {
    echo DOUBLE_ERASE_SEQUENCE;
    $modelLocation = promptUser('This is not a good answer. ' . MODEL_LOCATION_BUNDLE . ' => bundle, ' .
      MODEL_LOCATION_MODULE . ' => module ?');
  }

  return (int) $modelLocation;
}

/**
 * Asks the user for the module name
 *
 * @param string $bundlePath
 *
 * @return string
 */
function getModuleName(string $bundlePath) : string
{
  $question = 'In which module do you want to create the model ?';
  $moduleName = promptUser($question);

  while(!file_exists($bundlePath . $moduleName))
  {
    echo DOUBLE_ERASE_SEQUENCE;
    $moduleName = promptUser('This module does not exist.' . $question);
  }

  return $moduleName;
}

/**
 * @param array       $columns
 * @param string      $modelName
 * @param string      $functionsCode
 * @param string      $propertiesCode
 * @param string|null $propertiesTxt
 *
 * @throws OtraException
 */
function retrieveFunctionsAndProperties(
  array $columns,
  string $modelName,
  string &$functionsCode,
  string &$propertiesCode,
  ?string &$propertiesTxt = null
) : void
{
  /**
   * @var string $column
   * @var array $columnData
   */
  foreach ($columns as $column => $columnData)
  {
    $functionsCode .= PHP_EOL;
    /** @var string $columnDataType */
    $columnDataType = $columnData['type']; // e.g. int(5O)
    $typeSimple = $columnDataType === '' ? '' : getDataType($modelName, $column, $columnDataType); // e.g. int

    // Adds property declaration
    $propertiesCode .= SPACE_INDENT . 'protected ' . ($typeSimple === '' ? '' : $typeSimple . ' ') . '$' . $column .
      ';' . PHP_EOL;

    if ($propertiesTxt !== null)
      $propertiesTxt .= $column . ', ';

    checkDataType($modelName, $column, $columnDataType);
    /** @var string $startAccolade */
    addGettersAndSetters(
      $column,
      $typeSimple,
      $functionsCode
    );
  }
}

