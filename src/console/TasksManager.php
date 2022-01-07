<?php
declare(strict_types=1);
namespace otra\console;

use otra\OtraException;
use function otra\console\deployment\genClassMap\genClassMap;
use function otra\console\deployment\genClassMap\generateClassMap;
use const otra\bin\CACHE_PHP_INIT_PATH;
use const otra\cache\php\DIR_SEPARATOR;
use const otra\console\architecture\constants\
{ARG_BUNDLE_NAME, ARG_CONTROLLER_NAME, ARG_FORCE, ARG_INTERACTIVE, ARG_MODULE_NAME};
use const otra\console\deployment\updateConf\{UPDATE_CONF_ARG_MASK, UPDATE_CONF_ARG_ROUTE_NAME};
use function otra\console\architecture\createModule\createModule;
use function otra\console\deployment\genJsRouting\genJsRouting;
use function otra\console\deployment\updateConf\updateConf;
use function otra\console\architecture\createAction\createAction;
use function otra\console\helpAndTools\generateTaskMetadata\generateTaskMetadata;

/**
 * @author Lionel Péramo
 * @package otra
 */
abstract class TasksManager
{
  public const
    STRING_PAD_NUMBER_OF_CHARACTERS_FOR_OPTION_FORMATTING = 40,
    PAD_LENGTH_FOR_TASK_TITLE_FORMATTING = 27,
    PAD_LENGTH_FOR_TASK_OPTION_FORMATTING = 22,
    TASK_CLASS_MAP_TASK_PATH = 0,
    TASK_DESCRIPTION = 0,
    TASK_NAME = 1,
    TASK_PARAMETERS = 1,
    TASK_STATUS = 2,
    TASK_CATEGORY = 3,
    REQUIRED_PARAMETER = 'required',
    OPTIONAL_PARAMETER = 'optional';

  /**
   * List the available commands
   *
   * @param string $message The message to display before showing the commands
   */
  public static function showCommands(string $message) : void
  {
    define(__NAMESPACE__ . '\\HELP_BETWEEN_TASK_AND_COLON', 28);
    echo PHP_EOL, CLI_WARNING, $message, CLI_BASE, PHP_EOL, PHP_EOL;
    echo 'The available commands are : ', PHP_EOL . PHP_EOL, '  - ', CLI_BASE,
      str_pad('no argument', HELP_BETWEEN_TASK_AND_COLON),
      CLI_GRAY;
    echo ': ', CLI_INFO, 'Shows the available commands.', PHP_EOL;
    /** @var array<string, array<int,array>> $methods */
    $methods = require CACHE_PHP_INIT_PATH . 'tasksHelp.php';
    $category = '';

    foreach ($methods as $method => $paramsDesc)
    {
      if (isset($paramsDesc[self::TASK_CATEGORY]))
      {
        if ($category !== $paramsDesc[self::TASK_CATEGORY])
        {
          $category = $paramsDesc[self::TASK_CATEGORY];
          echo CLI_INFO_HIGHLIGHT, PHP_EOL, '*** ', $category, ' ***', REMOVE_BOLD_INTENSITY, PHP_EOL, PHP_EOL;
        }
      } else
      {
        $category = 'Other';
        echo CLI_INFO_HIGHLIGHT, PHP_EOL, '*** ', $category, ' ***', PHP_EOL, PHP_EOL;
      }

      echo CLI_GRAY, '  - ', CLI_BASE, str_pad($method, HELP_BETWEEN_TASK_AND_COLON), CLI_GRAY, ': ',
        CLI_INFO, $paramsDesc[self::TASK_DESCRIPTION], PHP_EOL;
    }

    echo END_COLOR;
  }

  /**
   * @param array  $tasksClassMap
   * @param string $otraTask
   * @param array  $argv
   *
   * @throws OtraException
   */
  public static function execute(array $tasksClassMap, string $otraTask, array $argv) : void
  {
    // If the class map does not exist yet, we create it and load it
    if (!file_exists(CACHE_PHP_INIT_PATH . 'ClassMap.php'))
    {
      echo CLI_WARNING,
        'We cannot use the console if the class mapping files do not exist ! We launch the generation of those files ...',
        END_COLOR, PHP_EOL;
      require $tasksClassMap['genClassMap'][TasksManager::TASK_CLASS_MAP_TASK_PATH] . '/genClassMapTask.php';
      genClassMap([]);

      // If the task was genClassMap...then we have nothing left to do !
      if ($otraTask === 'genClassMap')
        throw new OtraException(exit: true);

      require_once CACHE_PHP_INIT_PATH . 'ClassMap.php';
    }

    // _once as otherwise we cannot do multiple tasks in a row
    require_once $tasksClassMap[$otraTask][TasksManager::TASK_CLASS_MAP_TASK_PATH] . DIR_SEPARATOR . $otraTask . 'Task.php';

    switch($otraTask)
    {
      // Architecture
      case 'createAction':
      case 'createBundle':
      case 'createController':
      case 'createHelloWorld':
      case 'createModel':
      case 'createModule':
      case 'init':
        $otraTask = 'otra\\console\\architecture\\' . $otraTask . '\\' . $otraTask;
        $otraTask($argv);
        break;
      case 'createGlobalConstants':
        $otraTask = 'otra\\console\\architecture\\' . $otraTask . '\\' . $otraTask;
        $otraTask();
        break;
      // Database
      case 'sqlClean':
      case 'sqlCreateDatabase':
      case 'sqlCreateFixtures':
      case 'sqlExecute':
      case 'sqlImportFixtures':
      case 'sqlImportSchema':
        $otraTask = 'otra\\console\\database\\' . $otraTask . '\\' . $otraTask;
        $otraTask($argv);
        break;
      // Deployment
      case 'buildDev':
      case 'clearCache':
      case 'deploy':
      case 'genAssets':
      case 'genBootstrap':
      case 'genClassMap':
      case 'genServerConfig':
      case 'genSiteMap' :
      case 'genWatcher' :
        $otraTask = 'otra\\console\\deployment\\' . $otraTask . '\\' . $otraTask;
        $otraTask($argv);
        break;
      case 'updateConf':
        $otraTask = 'otra\\console\\deployment\\updateConf\\' . $otraTask;
        $otraTask($argv[UPDATE_CONF_ARG_MASK] ?? null, $argv[UPDATE_CONF_ARG_ROUTE_NAME] ?? null);
        break;
      case 'genJsRouting':
        $otraTask = 'otra\\console\\deployment\\' . $otraTask . '\\' . $otraTask;
        $otraTask();
        break;
      // Help and tools
      case 'convertImages' :
      case 'crypt':
      case 'hash':
      case 'help':
      case 'routes':
      case 'serve':
        $otraTask = 'otra\\console\\helpAndTools\\' . $otraTask . '\\' . $otraTask;
        $otraTask($argv);
        break;
      case 'generateTaskMetadata':
      case 'checkConfiguration' :
      case 'requirements' :
      case 'version' :
        $otraTask = 'otra\\console\\helpAndTools\\' . $otraTask . '\\' . $otraTask;
        $otraTask();
        break;
    }
  }
}

if (!defined(__NAMESPACE__ . '\\STRING_PAD_FOR_OPTION_FORMATTING'))
  define(
    __NAMESPACE__ . '\\STRING_PAD_FOR_OPTION_FORMATTING',
    str_repeat(' ', TasksManager::STRING_PAD_NUMBER_OF_CHARACTERS_FOR_OPTION_FORMATTING)
  );
