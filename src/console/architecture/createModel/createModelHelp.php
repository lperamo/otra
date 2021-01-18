<?php
declare(strict_types=1);

use otra\console\TasksManager;

if (!defined('OTRA_INTERACTIVE_MODE_NAME'))
{
  define('OTRA_INTERACTIVE_MODE_NAME', 'interactive');
  define('OTRA_INTERACTIVE_MODE_NAME_SECOND', ' mode.');
  define('OTRA_ONLY_USEFUL_AND_REQUIRED', 'Only useful (and required) for the ');
}

return [
  'Creates a model. ' . CLI_LIGHT_CYAN . 'how' . CLI_CYAN . ' parameter is ignored in interactive mode',
  [
    'bundle name' => 'The bundle in which the model have to be created',
    'method' => '1 => Creates from nothing' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '2 => One model from '. CLI_LIGHT_CYAN . 'schema.yml' . CLI_CYAN . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '3 => All models from ' . CLI_LIGHT_CYAN .'schema.yml' . CLI_CYAN . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . 'Useless with the ' . CLI_LIGHT_CYAN . OTRA_INTERACTIVE_MODE_NAME . CLI_CYAN .
      OTRA_INTERACTIVE_MODE_NAME_SECOND,
    OTRA_INTERACTIVE_MODE_NAME => 'If set to false, no question will be asked but the status messages are shown.' .
      PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . 'Defaults to true.',
    'model location' => 'Location of the model to create :' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '0 => in the ' . CLI_LIGHT_CYAN . 'bundle (default)' . CLI_CYAN . ' folder' .
      PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '1 => in the ' . CLI_LIGHT_CYAN . 'module' . CLI_CYAN . ' folder.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . OTRA_ONLY_USEFUL_AND_REQUIRED . CLI_LIGHT_CYAN .
      OTRA_INTERACTIVE_MODE_NAME . CLI_CYAN . OTRA_INTERACTIVE_MODE_NAME_SECOND,
    'module name' => 'Name of the module in which the model have to be created.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . OTRA_ONLY_USEFUL_AND_REQUIRED . CLI_LIGHT_CYAN
      . OTRA_INTERACTIVE_MODE_NAME . CLI_CYAN . OTRA_INTERACTIVE_MODE_NAME_SECOND,
    'model name' => 'Name of the model to create.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . OTRA_ONLY_USEFUL_AND_REQUIRED . CLI_LIGHT_CYAN .
      OTRA_INTERACTIVE_MODE_NAME .
      CLI_CYAN . OTRA_INTERACTIVE_MODE_NAME_SECOND . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . 'Required for mode 1 and mode 2, useless for mode 3.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . 'If the model exists or the model is not specified we import it.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . 'If it does not exist, we create it.',
    'model properties' => 'Names of the model properties separated by commas. Eg : ' . CLI_LIGHT_CYAN . 'id,name,age' .
      CLI_CYAN . '.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . OTRA_ONLY_USEFUL_AND_REQUIRED . CLI_LIGHT_CYAN .
      OTRA_INTERACTIVE_MODE_NAME .
      CLI_CYAN . ' mode when we want to create a new model' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . 'from nothing.',
    'model SQL types' => 'SQL types of the model properties separated by commas. Eg : ' . CLI_LIGHT_CYAN .
      'int,string,bool,bool' . CLI_CYAN . '.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . OTRA_ONLY_USEFUL_AND_REQUIRED . CLI_LIGHT_CYAN .
      OTRA_INTERACTIVE_MODE_NAME .
      CLI_CYAN . ' mode when we want to create a new model' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . 'from nothing.'
  ],
  [
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER
  ]
  ,
  'Architecture'
];
