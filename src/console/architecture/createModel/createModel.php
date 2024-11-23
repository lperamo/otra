<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture\createModel;

use DateTime;
use otra\OtraException;
use const otra\cache\php\SPACE_INDENT;
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT, CLI_WARNING, END_COLOR, SUCCESS};
use const otra\console\constants\DOUBLE_ERASE_SEQUENCE;
use function otra\console\promptUser;

/**
 * If we do not have the information of the type of this property in schema.yml,
 * we notice the user of that, and we stop the script
 *
 *
 * @throws OtraException
 */
function checkDataType(string $modelName, string $columnName, ?string $columnType) : void
{
  if (!isset($columnType))
  {
    echo CLI_ERROR, 'SCHEMA.YML => Model ', CLI_WARNING, $modelName, CLI_ERROR, ' : There are no type for the property ',
      CLI_WARNING, $columnName, CLI_ERROR, '.', END_COLOR;
    throw new OtraException(code: 1, exit: true);
  }
}

/**
 * Analyzes the SQL type of the current column in the schema.yml file and gives the PHP type in return
 *
 * @throws OtraException
 * @return string
 */
function getDataType(string $modelName, string $columnName, string $columnType) : string
{
  if (str_contains($columnType, 'char') || str_contains($columnType, 'text'))
    return 'string';

  if (str_contains($columnType, 'int'))
    return 'int';

  if (str_contains($columnType, 'float') || str_contains($columnType, 'double'))
    return 'float';

  if (str_contains($columnType, 'bool'))
    return 'bool';

  if (str_contains($columnType, 'timestamp') || str_contains($columnType, 'date'))
    return  DateTime::class;

  // If we don't know this type !
  echo CLI_ERROR, 'We don\'t know the type ', CLI_WARNING, $columnType, CLI_ERROR, ' in ', CLI_WARNING, $modelName, CLI_ERROR,
    ' for the property ', CLI_WARNING, $columnName, CLI_ERROR, ' !', END_COLOR;
  throw new OtraException(code: 1, exit: true);
}

/**
 * Returns the code of the getters and setters related to this property
 *
 * @param string $functions  Existing creation code for functions
 */
function addGettersAndSetters(string $columnName, string $columnType, string &$functions) : void
{
  $ucfirstColumnName = ucfirst($columnName);
  $functionEnd = $columnName . ';' . PHP_EOL . '  }' . PHP_EOL;
  $functions .= FUNCTION_START .
    'get' . $ucfirstColumnName . '()' . ($columnType === '' ? '' : ' : ' . $columnType)  . START_ACCOLADE . SPACE_INDENT .
    SPACE_INDENT . 'return $this->' . $functionEnd . PHP_EOL .
    FUNCTION_START .
    'set' . $ucfirstColumnName . '(' . ($columnType === '' ? '' : $columnType . ' ') . '$' . $columnName . ') : void' . START_ACCOLADE . SPACE_INDENT . SPACE_INDENT . '$this->' .
    $columnName . ' = ' . '$' . $functionEnd;
}

/**
 * Shows the success sentence after the successful creation of a model.
 *
 * @param string      $bundleName    The bundle in which the model have to be created
 * @param string|null $propertiesTxt
 */
function modelCreationSuccess(string $bundleName, string $modelName, ?string $propertiesTxt = null) : void
{
  echo 'The model ', CLI_INFO_HIGHLIGHT, $modelName, END_COLOR, ' has been created in the bundle ', CLI_INFO_HIGHLIGHT,
    $bundleName, END_COLOR;

  if (null !== $propertiesTxt)
    echo ' with those properties [', substr($propertiesTxt, 0, strlen($propertiesTxt) - 2), ']';

  echo '.', SUCCESS;
}

/**
 * Creates the 'models' folder if it doesn't exist and then creates the model file that we wanted.
 *
 * @param int    $modelLocation Location of the model to create
 *                              0 => in the bundle (default) folder
 *                              1 => in the module folder.
 * @param string $bundleName    The bundle in which the model have to be created
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

  if (str_contains($functions, DateTime::class))
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
 *
 * @return array{0:string, 1:string} [$modelFullName, $modelExists]
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
 * @param array<string, array> $columns
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
