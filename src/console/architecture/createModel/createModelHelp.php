<?php
/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture\createModel;

use otra\console\TasksManager;
use const otra\console\{CLI_INFO, CLI_INFO_HIGHLIGHT, STRING_PAD_FOR_OPTION_FORMATTING};

const
  OTRA_INTERACTIVE_MODE_NAME = 'interactive',
  OTRA_INTERACTIVE_MODE_NAME_SECOND = ' mode.',
  OTRA_ONLY_USEFUL_AND_REQUIRED = 'Only useful (and required) for the ';

return [
  'Creates a model. ' . CLI_INFO_HIGHLIGHT . 'how' . CLI_INFO . ' parameter is ignored in interactive mode',
  [
    'bundle-name' => 'The bundle in which the model have to be created',
    'method' => '1 => Creates from nothing' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '2 => One model from '. CLI_INFO_HIGHLIGHT . 'schema.yml' . CLI_INFO . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '3 => All models from ' . CLI_INFO_HIGHLIGHT .'schema.yml' . CLI_INFO . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . 'Useless with the ' . CLI_INFO_HIGHLIGHT . OTRA_INTERACTIVE_MODE_NAME . CLI_INFO .
      OTRA_INTERACTIVE_MODE_NAME_SECOND,
    OTRA_INTERACTIVE_MODE_NAME => 'If set to false, no question will be asked but the status messages are shown.' .
      PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . 'Defaults to true.',
    'model-location' => 'Location of the model to create :' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '0 => in the ' . CLI_INFO_HIGHLIGHT . 'bundle (default)' . CLI_INFO . ' folder' .
      PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '1 => in the ' . CLI_INFO_HIGHLIGHT . 'module' . CLI_INFO . ' folder.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . OTRA_ONLY_USEFUL_AND_REQUIRED . CLI_INFO_HIGHLIGHT .
      OTRA_INTERACTIVE_MODE_NAME . CLI_INFO . OTRA_INTERACTIVE_MODE_NAME_SECOND,
    'module-name' => 'Name of the module in which the model have to be created.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . OTRA_ONLY_USEFUL_AND_REQUIRED . CLI_INFO_HIGHLIGHT
      . OTRA_INTERACTIVE_MODE_NAME . CLI_INFO . OTRA_INTERACTIVE_MODE_NAME_SECOND,
    'model-name' => 'Name of the model to create.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . OTRA_ONLY_USEFUL_AND_REQUIRED . CLI_INFO_HIGHLIGHT .
      OTRA_INTERACTIVE_MODE_NAME .
      CLI_INFO . OTRA_INTERACTIVE_MODE_NAME_SECOND . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . 'Required for mode 1 and mode 2, useless for mode 3.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . 'If the model exists or the model is not specified we import it.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . 'If it does not exist, we create it.',
    'model-properties' => 'Names of the model properties separated by commas. Eg : ' . CLI_INFO_HIGHLIGHT . 'id,name,age' .
      CLI_INFO . '.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . OTRA_ONLY_USEFUL_AND_REQUIRED . CLI_INFO_HIGHLIGHT .
      OTRA_INTERACTIVE_MODE_NAME .
      CLI_INFO . ' mode when we want to create a new model' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . 'from nothing.',
    'model-sql-types' => 'SQL types of the model properties separated by commas. Eg : ' . CLI_INFO_HIGHLIGHT .
      'int,string,bool,bool' . CLI_INFO . '.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . OTRA_ONLY_USEFUL_AND_REQUIRED . CLI_INFO_HIGHLIGHT .
      OTRA_INTERACTIVE_MODE_NAME .
      CLI_INFO . ' mode when we want to create a new model' . PHP_EOL .
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
