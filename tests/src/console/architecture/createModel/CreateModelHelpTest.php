<?php
declare(strict_types=1);

namespace src\console\architecture\createModel;

use otra\console\TasksManager;
use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\console\
{
  CLI_BASE,
  CLI_GRAY,
  CLI_INFO,
  CLI_INFO_HIGHLIGHT,
  END_COLOR,
  STRING_PAD_FOR_OPTION_FORMATTING};
use const otra\bin\TASK_CLASS_MAP_PATH;

/**
 * @runTestsInSeparateProcesses
 */
class CreateModelHelpTest extends TestCase
{
  private const
    OTRA_TASK_CREATE_MODEL = 'createModel',
    OTRA_TASK_HELP = 'help',
    OTRA_INTERACTIVE_MODE_NAME = 'interactive',
    OTRA_INTERACTIVE_MODE_NAME_SECOND = ' mode.',
    OTRA_ONLY_USEFUL_AND_REQUIRED = 'Only useful (and required) for the ',
    LABEL_PLUS = '   + ';

  // fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function test() : void
  {
    $this->expectOutputString(
      CLI_BASE .
      str_pad(self::OTRA_TASK_CREATE_MODEL, TasksManager::PAD_LENGTH_FOR_TASK_TITLE_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO .
      'Creates a model. ' . CLI_INFO_HIGHLIGHT . 'how' . CLI_INFO . ' parameter is ignored in interactive mode' .
      PHP_EOL . CLI_INFO_HIGHLIGHT .
      self::LABEL_PLUS . str_pad('bundle-name', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'The bundle in which the model have to be created' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      self::LABEL_PLUS . str_pad('method', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . '1 => Creates from nothing' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '2 => One model from '. CLI_INFO_HIGHLIGHT . 'schema.yml' . CLI_INFO . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '3 => All models from ' . CLI_INFO_HIGHLIGHT .'schema.yml' . CLI_INFO . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . 'Useless with the ' . CLI_INFO_HIGHLIGHT . self::OTRA_INTERACTIVE_MODE_NAME . CLI_INFO .
      self::OTRA_INTERACTIVE_MODE_NAME_SECOND . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      self::LABEL_PLUS . str_pad('interactive', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'If set to false, no question will be asked but the status messages are shown.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . 'Defaults to true.' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      self::LABEL_PLUS . str_pad('model-location', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO . 'Location of the model to create :' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '0 => in the ' . CLI_INFO_HIGHLIGHT . 'bundle (default)' . CLI_INFO . ' folder' .
      PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '1 => in the ' . CLI_INFO_HIGHLIGHT . 'module' . CLI_INFO . ' folder.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . self::OTRA_ONLY_USEFUL_AND_REQUIRED . CLI_INFO_HIGHLIGHT .
      self::OTRA_INTERACTIVE_MODE_NAME . CLI_INFO . self::OTRA_INTERACTIVE_MODE_NAME_SECOND . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      self::LABEL_PLUS . str_pad('module-name', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO .
      'Name of the module in which the model have to be created.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . self::OTRA_ONLY_USEFUL_AND_REQUIRED . CLI_INFO_HIGHLIGHT
      . self::OTRA_INTERACTIVE_MODE_NAME . CLI_INFO . self::OTRA_INTERACTIVE_MODE_NAME_SECOND . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      self::LABEL_PLUS . str_pad('model-name', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO .
      'Name of the model to create.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . self::OTRA_ONLY_USEFUL_AND_REQUIRED . CLI_INFO_HIGHLIGHT .
      self::OTRA_INTERACTIVE_MODE_NAME .
      CLI_INFO . self::OTRA_INTERACTIVE_MODE_NAME_SECOND . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . 'Required for mode 1 and mode 2, useless for mode 3.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . 'If the model exists or the model is not specified we import it.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . 'If it does not exist, we create it.' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      self::LABEL_PLUS . str_pad('model-properties', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO .
      'Names of the model properties separated by commas. Eg : ' . CLI_INFO_HIGHLIGHT . 'id,name,age' .
      CLI_INFO . '.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . self::OTRA_ONLY_USEFUL_AND_REQUIRED . CLI_INFO_HIGHLIGHT .
      self::OTRA_INTERACTIVE_MODE_NAME .
      CLI_INFO . ' mode when we want to create a new model' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . 'from nothing.' . PHP_EOL .
      CLI_INFO_HIGHLIGHT .
      self::LABEL_PLUS . str_pad('model-sql-types', TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) .
      CLI_GRAY . ': ' . CLI_INFO_HIGHLIGHT . '(' . TasksManager::OPTIONAL_PARAMETER .
      ') ' . CLI_INFO .
      'SQL types of the model properties separated by commas. Eg : ' . CLI_INFO_HIGHLIGHT .
      'int,string,bool,bool' . CLI_INFO . '.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . self::OTRA_ONLY_USEFUL_AND_REQUIRED . CLI_INFO_HIGHLIGHT .
      self::OTRA_INTERACTIVE_MODE_NAME .
      CLI_INFO . ' mode when we want to create a new model' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . 'from nothing.' . PHP_EOL .
      END_COLOR
    );

    TasksManager::execute(
      require TASK_CLASS_MAP_PATH,
      self::OTRA_TASK_HELP,
      ['otra.php', self::OTRA_TASK_HELP, self::OTRA_TASK_CREATE_MODEL]
    );
  }
}
