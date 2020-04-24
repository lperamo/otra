<?php
declare(strict_types=1);

define('DEFAULT_BDD_SCHEMA_NAME', 'schema.yml');
define('MODEL_DIRECTORY', 'models/');

/**
 * If we do not have the information of the type of this property in schema.yml,
 * we notice the user of that and we stop the script
 *
 * @param string $modelName
 * @param string $columnName
 * @param string $type
 */
function checkDataType(string $modelName, string $columnName, string $type)
{
  if (false === isset($type))
  {
    echo CLI_RED, 'SCHEMA.YML => Model ', CLI_YELLOW, $modelName, CLI_RED, ' : There are no type for the property ', CLI_YELLOW,
    $columnName, CLI_RED, '.', END_COLOR;
    exit (1);
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
 */
function getDataType(string $modelName, string $columnName, string $type) : string
{
  if (false !== strpos($type, 'char') || false !== strpos($type, 'text'))
    return 'string';

  if (false !== strpos($type, 'int'))
    return 'int';

  if (false !== strpos($type, 'float') || false !== strpos($type, 'double'))
    return 'float';

  if (false !== strpos($type, 'bool'))
    return 'bool';

  if (false !== strpos($type, 'timestamp') || false !== strpos($type, 'date'))
    return 'Date';

  // If we don't know this type !
  echo CLI_RED, 'We don\'t know the type ', CLI_YELLOW, $type, CLI_RED, ' in ', CLI_YELLOW, $modelName, CLI_RED, ' for the property 
  ', CLI_YELLOW, $columnName, CLI_RED, ' !', END_COLOR;
  exit(1);
}

/**
 * Returns the code of the getters and setters related to this property
 *
 * @param string $columnName
 * @param string $type          Column type
 * @param string $functions     Existing creation code for functions
 * @param string $functionStart Functions start code
 * @param string $startAccolade Code for the starting accolade...
 */
function addGettersAndSetters(string $columnName, string $type, string &$functions, string $functionStart, string $startAccolade)
{
  $functionEnd = $columnName . ';' . PHP_EOL . '  }' . PHP_EOL;
  $functions .= $functionStart .
    'get' . ucfirst($columnName) . '() : ' . $type . $startAccolade . '    $this->' . $columnName . ' = ' . $functionEnd
    . PHP_EOL . $functionStart .
    'set' . ucfirst($columnName) . '($' . $columnName . ')' . $startAccolade . '    return $this->' . $functionEnd;
}
/**
 * Shows the success sentence after the successful creation of a model.
 *
 * @param string      $bundleName
 * @param string      $modelName
 * @param string|null $propertiesTxt
 */
function modelCreationSuccess(string $bundleName, string $modelName, string $propertiesTxt = null)
{
  echo CLI_GREEN, 'The model ', CLI_YELLOW, $modelName, CLI_GREEN, ' has been created in the bundle ', CLI_YELLOW, $bundleName,
  CLI_GREEN;

  if (null !== $propertiesTxt)
    echo ' with those properties [', substr($propertiesTxt, 0, strlen($propertiesTxt) - 2), ']';

  echo '.', END_COLOR, PHP_EOL;
}

/**
 * Creates the 'models' folder if it doesn't exist and then creates the model file that we wanted.
 *
 * @param string $bundlePath
 * @param string $modelName
 * @param string $modelFullName
 * @param string $propertiesCode
 * @param string $functions
 */
function writeModelFile(string $bundlePath, string $modelName, string $modelFullName, string $propertiesCode, string $functions)
{
  // If the 'models' folder doesn't exist => creates it.
  $modelsPath = $bundlePath . MODEL_DIRECTORY;

  if (false === file_exists($modelsPath))
    mkdir($modelsPath, 0755);

  // Creates the model with all the content that we gathered.
  $fp = fopen($modelsPath . ucfirst($modelFullName), 'w');
  // TODO remove the CMS thing !!! this folder can be rename so it is a critical mistake !
  fwrite($fp, '<?php' . PHP_EOL .
    'namespace bundles\CMS\models;' . PHP_EOL . PHP_EOL .
    'class ' . ucfirst($modelName) . PHP_EOL .
    '{' . PHP_EOL . $propertiesCode . $functions . '}' . PHP_EOL . '?>' . PHP_EOL);
  fclose($fp);
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
 * @return string '1' => bundle or '2' => model
 */
function getBundleModule() : string
{
  $bundleModule = promptUser('Is it a bundle model or a module model ? (1 => bundle, 2 => module)');

  while ('1' !== $bundleModule && '2' !== $bundleModule)
  {
    echo DOUBLE_ERASE_SEQUENCE;
    $bundleModule = promptUser('This is not a good answer. 1 => bundle, 2 => module ?');
  }

  return $bundleModule;
}

/**
 * Asks the user for the module name
 *
 * @param string $bundleName
 * @param string $bundlePath
 *
 * @return string
 */
function getModuleName(string $bundleName, string $bundlePath) : string
{
  $question = 'In which module do you want to create the model ?';
  $moduleName = promptUser($question);

  while(false === file_exists($bundlePath . $moduleName))
  {
    echo DOUBLE_ERASE_SEQUENCE;
    $moduleName = promptUser('This module does not exist.' . $question);
  }

  echo DOUBLE_ERASE_SEQUENCE, 'A model in the bundle ', CLI_YELLOW, $bundleName, END_COLOR, ' for the module ', CLI_YELLOW,
  $moduleName, END_COLOR, ' ...', PHP_EOL;

  return $moduleName;
}

require CONSOLE_PATH . 'tools.php';

$bundlesPath = BASE_PATH . 'bundles/';

/** 1. BUNDLE NAME ... if we don't have it via the given console parameters */
if (false === isset($argv[2]) || false === file_exists($bundlesPath . $argv[2]))
{
  $argv[2] = promptUser('You did not specified the name of the bundle or the bundle does not exist. What is it ?');

  while(false === file_exists($bundlesPath . $argv[2]))
  {
    echo DOUBLE_ERASE_SEQUENCE;
    $argv[2] = promptUser('This bundle does not exist ! Try once again :');
  }

  // We clean the screen
  echo DOUBLE_ERASE_SEQUENCE;
}

// We add the chosen bundle name to the path
$bundleName = $argv[2];
$bundlePath = $bundlesPath . $bundleName . '/';

echo 'We use the ', CLI_CYAN, $argv[2], END_COLOR, ' bundle.', PHP_EOL;
$possibleChoices = [1,2,3];

/** 2. MODEL CREATION MODE ... if we don't have it via the given console parameters */
if (false === isset($argv[3]) || false === in_array($argv[3], $possibleChoices))
{
  echo CLI_YELLOW, 'You did not specified how do you want to create it or this creation mode does not exist. How do you want to create it ?', PHP_EOL, PHP_EOL,
    '1 => only one model from nothing', PHP_EOL,
    '2 => one specific model from the ', CLI_CYAN, DEFAULT_BDD_SCHEMA_NAME, CLI_YELLOW, PHP_EOL,
    '3 => all from the ', CLI_CYAN, DEFAULT_BDD_SCHEMA_NAME, CLI_YELLOW, PHP_EOL, PHP_EOL;

  $argv[3] = promptUser('Your choice ?');

  $wrongMode = 'This creation mode does not exist ! Try once again :';

  // If the creation mode requested is incorrect, we ask once more until we are satisfied with the user answer
  while(false === in_array($argv[3], $possibleChoices))
  {
    echo DOUBLE_ERASE_SEQUENCE;
    $argv[3] = promptUser($wrongMode, $wrongMode);
  }

  // We clean the screen (8 lines to erase !)
  echo DOUBLE_ERASE_SEQUENCE, DOUBLE_ERASE_SEQUENCE, DOUBLE_ERASE_SEQUENCE, DOUBLE_ERASE_SEQUENCE;
}

/**************************
 * ONE MODEL FROM NOTHING *
 **************************/
if ('1' === $argv[3])
{
  echo 'We will create one model from nothing.', PHP_EOL;
  $bundleModule = getBundleModule();
  $modelNameQuestion = 'What is the name of your new model ? (camelCase, no need to put .php)';
  echo DOUBLE_ERASE_SEQUENCE;

  if('1' === $bundleModule) /** BUNDLE */
  {
    echo 'A model for the bundle ', CLI_YELLOW, $bundleName, END_COLOR, ' ...', PHP_EOL;
    $path = $bundlePath;
  } else /** MODULE */
  {
    $moduleName = getModuleName($bundleName, $bundlePath);
    $path = $bundlePath . $moduleName . '/';
  }

  $modelName = promptUser($modelNameQuestion, 'Bad answer. ' . $modelNameQuestion);
  list($modelFullName, $modelExists) = getModelFullNameAndModelExists($modelName, $modelNameQuestion);

  while (true === file_exists($path . MODEL_DIRECTORY . $modelFullName))
  {
    echo DOUBLE_ERASE_SEQUENCE;
    $modelName = promptUser($modelExists, $modelExists);
    // We update the informations right now in order to deliver precise error messages
    list($modelFullName, $modelExists) = getModelFullNameAndModelExists($modelName, $modelNameQuestion);
  }

  echo DOUBLE_ERASE_SEQUENCE, DOUBLE_ERASE_SEQUENCE, 'The model ', CLI_YELLOW, $modelName, END_COLOR, ' will be created from nothing.',
  PHP_EOL;

  $propertiesTxt = $functions = $propertiesCode = '';

  $propertyText = 'Which property do you want to add ? (lowercase, type \'no!more\' if you don\'t want any other property)';
  $propertyErrorText = 'You did not type anything. ' . $propertyText;
  $property = promptUser($propertyText, $propertyErrorText);
  $functionStart = SPACE_INDENT . 'public function ';

  // Ask until we don't want any other properties.
  while ('no!more' !== $property)
  {
    $functions .= PHP_EOL;

    // Adds property declaration
    $propertiesCode .= SPACE_INDENT . 'protected $' . $property . ';' . PHP_EOL;
    $propertiesTxt .= $property . ', ';

    // Adds getters and setters
    $functionEnd = ucfirst($property) . '()' . PHP_EOL . '  {' . PHP_EOL . '  }' . PHP_EOL;
    $functions .= $functionStart . 'get' .  $functionEnd . PHP_EOL . $functionStart . 'set' .  $functionEnd;

    // Do we want another properties ?
    $property = promptUser(DOUBLE_ERASE_SEQUENCE . $propertyText, $propertyErrorText);
  }

  writeModelFile($path, $modelName, $modelFullName, $propertiesCode, $functions);
  // We cleans our questions ...
  echo DOUBLE_ERASE_SEQUENCE, ERASE_SEQUENCE;
  modelCreationSuccess($bundleName, $modelName);
} else
{
  $schemaPath = realpath(BASE_PATH . 'config/data/yml/schema.yml');
  require BASE_PATH . 'vendor/symfony/yaml/Yaml.php';
  $schemaData = Symfony\Component\Yaml\Yaml::parse(file_get_contents($schemaPath));

  // Those variables are used for code creation
  $functionStart = SPACE_INDENT . 'public function ';
  $startAccolade = PHP_EOL . SPACE_INDENT . '{' . PHP_EOL;

  /*****************************
   * ONE MODEL FROM SCHEMA.YML *
   *****************************/
  if('2' === $argv[3])
  {
    $functions = $propertiesCode = '';
    echo 'We will create one model from ', CLI_CYAN, DEFAULT_BDD_SCHEMA_NAME, CLI_YELLOW, '.', PHP_EOL;
    $bundleModule = getBundleModule();
    $modelNameQuestion = 'What is the name of the model that you want to create from \'schema.yml\' ? (camelCase, no need to put .php)';
    // We cleans the bundle/module question
    echo DOUBLE_ERASE_SEQUENCE;

    if ('1' === $bundleModule)/** BUNDLE */
    {
      $path = $bundlePath;
    } else /** MODULE */
    {
      $moduleName = getModuleName($bundleName, $bundlePath);
      $path = $bundlePath . $moduleName . '/';

      // We cleans the module name question
      echo DOUBLE_ERASE_SEQUENCE;
    }

    $modelName = promptUser($modelNameQuestion, 'Bad answer. ' . $modelNameQuestion);
    $modelFullName = $modelName . '.php';

    $availableTables = array_keys($schemaData);
    $modelExists = file_exists($path . MODEL_DIRECTORY . $modelFullName);
    $tableExists = in_array($modelName, $availableTables, true);

    // If the model exists, we ask once more until we are satisfied with the user answer (we can't override it as of now)
    while (true === $modelExists || false === $tableExists)
    {
      echo DOUBLE_ERASE_SEQUENCE;
      $errorLabel = '';

      if (true === $modelExists)
        $errorLabel .= 'This model \'' . $modelName . '\' already exists. ';

      if (false === $tableExists)
        $errorLabel .= 'The schema does not contains this table (maybe ... check the case).';

      $errorLabel .= $modelNameQuestion;

      $modelName = promptUser($errorLabel, $errorLabel);
      $modelFullName = $modelName . '.php';

      $modelExists = file_exists($path . MODEL_DIRECTORY . $modelFullName);
      $tableExists = in_array($modelName, $availableTables, true);
    }

    echo DOUBLE_ERASE_SEQUENCE, ERASE_SEQUENCE, 'Creating the model ', CLI_YELLOW, $modelName, END_COLOR, ' for the bundle ',
    CLI_YELLOW,     ($bundleName), ' ...', PHP_EOL;

    $functions = $propertiesCode = $propertiesTxt = '';

    foreach ($schemaData[$modelName]['columns'] as $column => &$columnData)
    {
      $functions .= PHP_EOL;

      // Adds property declaration
      $propertiesCode .= SPACE_INDENT . 'protected $' . $column . ';' . PHP_EOL;
      $propertiesTxt .= $column . ', ';

      checkDataType($modelName, $column, $columnData['type']);
      addGettersAndSetters(
        $column,
        getDataType($modelName, $column, $columnData['type']),
        $functions,
        $functionStart,
        $startAccolade
      );
    }

    writeModelFile($path, $modelName, $modelFullName, $propertiesCode, $functions);
    modelCreationSuccess($bundleName, $modelName, $propertiesTxt);
  }
  /******************************
   * ALL MODELS FROM SCHEMA.YML *
   ******************************/
  else
  {
    echo 'We will create all models from ', CLI_CYAN, DEFAULT_BDD_SCHEMA_NAME, CLI_YELLOW, '.', PHP_EOL;
    $bundleModule = getBundleModule();

    if ('1' === $bundleModule) /** BUNDLE */
    {
      // We cleans the bundle/module question
      echo DOUBLE_ERASE_SEQUENCE, 'Creating all the models for the bundle ', CLI_YELLOW, $bundleName, END_COLOR, ' ...',
      PHP_EOL;

      $path = $bundlePath;
    } else /** MODULE */
    {
      // We cleans the bundle/module question
      echo DOUBLE_ERASE_SEQUENCE;

      $moduleName = getModuleName($bundleName, $bundlePath);
      $path = $bundlePath . $moduleName . '/';

      // We cleans the module name question
      echo DOUBLE_ERASE_SEQUENCE, 'Creating all the models for the bundle ', CLI_YELLOW, $bundleName, END_COLOR, ' in the module ',
      CLI_YELLOW, $moduleName, END_COLOR, ' ...', PHP_EOL;
    }

    foreach ($schemaData as $modelName => &$model)
    {
      $modelFullName = $modelName . '.php';
      $functions = $propertiesCode = '';

      foreach ($model['columns'] as $column => &$columnData)
      {
        $functions .= PHP_EOL;

        // Adds property declaration
        $propertiesCode .= SPACE_INDENT . 'protected $' . $column . ';' . PHP_EOL;

        checkDataType($modelName, $column, $columnData['type']);
        addGettersAndSetters(
          $column,
          getDataType($modelName, $column, $columnData['type']),
          $functions,
          $functionStart,
          $startAccolade
        );
      }

      writeModelFile($path, $modelName, $modelFullName, $propertiesCode, $functions);
      modelCreationSuccess($bundleName, $modelName);
    }
  }
}
?>
