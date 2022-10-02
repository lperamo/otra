<?php
/**
 * @author Lionel Péramo
 * @package otra\console\helpAndTools
 */
declare(strict_types=1);
namespace otra\cache\php {
  defined(__NAMESPACE__ . '\\OTRA_PROJECT')
    || define(__NAMESPACE__ . '\\OTRA_PROJECT', str_contains(__DIR__, 'vendor'));
}

namespace otra\console\helpAndTools\generateTaskMetadata
{
  use otra\config\AllConfig;
  use FilesystemIterator;
  use otra\console\TasksManager;
  use otra\OtraException;
  use RecursiveDirectoryIterator;
  use RecursiveIteratorIterator;
  use SplFileInfo;
  use function otra\console\convertArrayFromVarExportToShortVersion;
  use function otra\console\deployment\genClassMap\genClassMap;
  use const otra\bin\CACHE_PHP_INIT_PATH;
  use const otra\cache\php\init\CLASSMAP;
  use const otra\cache\php\
  {
    APP_ENV,
    BASE_PATH,
    CACHE_PATH,
    CONSOLE_PATH,
    CORE_PATH,
    CLASS_MAP_PATH,
    DEV,
    DIR_SEPARATOR,
    OTRA_PROJECT,
    PROD,
    SPACE_INDENT
  };
  use const otra\console\{CLI_BASE, SUCCESS};

  const PHP_INIT_FILE_BEGINNING =
    '<?php declare(strict_types=1);namespace otra\\cache\\php\\init;use const \\otra\\cache\\php\\{CORE_PATH};return ',
    COMPLETIONS_SPACES_STR_PAD = 28;

  /**
   * @throws OtraException
   * @return void
   */
  function generateTaskMetadata(): void
  {
    if (!defined('\otra\cache\php\BASE_PATH'))
    {
      // @codeCoverageIgnoreStart
      // BASE_PATH calculation
      $temporaryBasePath = '/../../../..';

      if (OTRA_PROJECT)
        $temporaryBasePath .= '/../../..'; // long path from vendor

      define(__NAMESPACE__ . '\\CONSTANTS_ENDING_PATH', '/config/constants.php');
      define(
        __NAMESPACE__ . '\\CONSTANTS_PATH',
        realpath(__DIR__ . $temporaryBasePath . CONSTANTS_ENDING_PATH)
      );

      if (CONSTANTS_PATH !== false)
        require CONSTANTS_PATH;
      else
      {
        $_SERVER[APP_ENV] = DEV;
        require __DIR__ . '/../../../..' . CONSTANTS_ENDING_PATH;
      }

      require CONSOLE_PATH . 'colors.php';
      // @codeCoverageIgnoreEnd
    }

    // Generating the class map if needed
    if (!file_exists(CLASS_MAP_PATH))
    {
      require CONSOLE_PATH . 'deployment/genClassMap/genClassMapTask.php';
      genClassMap([]);
    }

    // loading the class map if not defined
    if (!defined('otra\\cache\\php\\init\\CLASSMAP'))
      require CLASS_MAP_PATH;

    spl_autoload_register(function (string $className): void {
      require CLASSMAP[$className];
    });

    if (!defined(__NAMESPACE__ . 'PHP_CACHE_FOLDER'))
      define(__NAMESPACE__ . 'PHP_CACHE_FOLDER', CACHE_PATH . 'php/');

    /**************************************
     * HELP AND TASK CLASS MAP GENERATION *
     **************************************/

    // temporarily forces looking into the development configuration
    $_SERVER[APP_ENV] = DEV;
    $foldersToCheckForTasks = array_unique([CONSOLE_PATH, ...AllConfig::$taskFolders ?? []]);
    $_SERVER[APP_ENV] = PROD;

    $helpFileContent = [];
    $taskClassMap = [];

    foreach ($foldersToCheckForTasks as $foldersToCheckForTask)
    {
      $dir_iterator = new RecursiveDirectoryIterator($foldersToCheckForTask, FilesystemIterator::SKIP_DOTS);
      $iterator = new RecursiveIteratorIterator($dir_iterator);

      /** @var SplFileInfo $entry */
      foreach ($iterator as $entry)
      {
        $pathname = $entry->getPathname();

        if (!str_contains($pathname, 'Help.'))
          continue;

        $pathname = str_replace('\\', DIR_SEPARATOR, $pathname);
        $consoleTask = mb_substr($pathname, mb_strrpos($pathname, DIR_SEPARATOR) + 1);
        $consoleTask = mb_substr($consoleTask, 0, mb_strrpos($consoleTask, 'Help'));
        $helpFileContent[$consoleTask] = require $pathname;
        $taskClassMap[$consoleTask] = [
          dirname($pathname),
          $helpFileContent[$consoleTask][TasksManager::TASK_STATUS]
        ];
      }
    }

    $tasks = array_keys($helpFileContent);
    $taskCategories = array_column($helpFileContent, TasksManager::TASK_CATEGORY);
    // sorts alphabetically the tasks and grouping them by category
    array_multisort($taskCategories, SORT_ASC, $tasks, SORT_ASC, $helpFileContent);
    // beware! require_once instead of require only needed for automated tests!
    require_once CONSOLE_PATH . 'tools.php';

    // Generate the tasks descriptions in a cached file.
    $helpFileFinalContent = PHP_INIT_FILE_BEGINNING . var_export($helpFileContent, true);
    $helpFileFinalContent = convertArrayFromVarExportToShortVersion($helpFileFinalContent) . ';' . PHP_EOL;

    file_put_contents(CACHE_PHP_INIT_PATH . 'tasksHelp.php', $helpFileFinalContent);

    // Generate the tasks paths in a cached file. We change the path in the task path that can be replaced by constants
    $taskClassMap = convertArrayFromVarExportToShortVersion(
      PHP_INIT_FILE_BEGINNING . var_export($taskClassMap, true) . ';'
    );

    $taskClassMapFinalContent = str_replace("'" . BASE_PATH,
        'BASE_PATH.\'',
        str_replace("'" . CORE_PATH, 'CORE_PATH.\'', $taskClassMap),
        $basePathReplacements
      ) . PHP_EOL;

    if ($basePathReplacements > 0)
      $taskClassMapFinalContent = str_replace('\\{CORE_PATH', '\\{BASE_PATH,CORE_PATH', $taskClassMapFinalContent);

    file_put_contents(
      CACHE_PHP_INIT_PATH . 'tasksClassMap.php',
      $taskClassMapFinalContent
    );

    if (PHP_SAPI === 'cli')
      echo CLI_BASE, 'Generation of help and task class map done', SUCCESS;

    /********************************
     * SHELL COMPLETIONS GENERATION *
     ********************************/

    // if we launch this task, the console will already launch this task before so for now, we check the variable existence
    $shellCompletionsContent = '#!/usr/bin/env bash' . PHP_EOL
      . 'typeset BLC="\033[1;96m"' . PHP_EOL // CLI_INFO_HIGHLIGHT
      . 'typeset WHI="\033[0;38m"' . PHP_EOL // CLI_BASE
      . 'typeset CYA="\033[0;36m"' . PHP_EOL //CLI_INFO
      . 'typeset ECO="\033[0m"' . PHP_EOL // END_COLOR
      . 'typeset -a OTRA_COMMANDS=(' . PHP_EOL;

    $taskDescription = '';

    $taskCategoriesLong = $taskCategories = [];

    foreach ($tasks as $consoleTask)
    {
      $shellCompletionsContent .= SPACE_INDENT . '\'' . $consoleTask . '\'' . PHP_EOL;
      $taskCategory = ucfirst($helpFileContent[$consoleTask][TasksManager::TASK_CATEGORY]);

      /** @var string $taskCategoryLong */
      if (!in_array($taskCategory, $taskCategories))
      {
        $taskCategories[] = $taskCategory;
        $taskCategoryLong = 'CAT_'
          . str_replace(' ', '_', strtoupper($helpFileContent[$consoleTask][TasksManager::TASK_CATEGORY]));
        $taskCategoriesLong[] = $taskCategoryLong;
      }

      $taskDescription .= SPACE_INDENT . '"${' . $taskCategoryLong . '} '
        . str_pad($consoleTask, COMPLETIONS_SPACES_STR_PAD) . ': ${CYA}' .
        $helpFileContent[$consoleTask][TasksManager::TASK_DESCRIPTION] . '${ECO}"'
        . PHP_EOL;
    }

    $shellCompletionsContent .= ');' . PHP_EOL . PHP_EOL;

    foreach ($taskCategories as $taskCategoryKey => $taskCategory)
    {
      $shellCompletionsContent .= 'typeset ' . $taskCategoriesLong[$taskCategoryKey] . '="${BLC}[ '
        . str_pad($taskCategory, strlen('Help and tools'), ' ', STR_PAD_BOTH) . ' ]${WHI}";' .
        PHP_EOL;
    }

    $shellCompletionsContent .= PHP_EOL . 'typeset -a OTRA_COMMANDS_DESCRIPTIONS=(' . PHP_EOL
      . $taskDescription . ')' . PHP_EOL . PHP_EOL
      . 'export OTRA_COMMANDS' . PHP_EOL . 'export OTRA_COMMANDS_DESCRIPTIONS' . PHP_EOL;

    file_put_contents(CONSOLE_PATH . 'shellCompletions/shellCompletions.sh', $shellCompletionsContent);

    if (PHP_SAPI === 'cli')
      echo CLI_BASE, 'Generation of shell completions script done', SUCCESS;
  }
}
