<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\helpAndTools
 */

// If we do not come from the 'otra' command...
use otra\console\TasksManager;

if (!defined('BASE_PATH'))
{
  // @codeCoverageIgnoreStart
  define('OTRA_PROJECT', str_contains(__DIR__, 'vendor'));

  // BASE_PATH calculation
  $temporaryBasePath = (OTRA_PROJECT
    ? '/../../../../../../..' // long path from vendor
    : '/..'
  );

  define('CONSTANTS_ENDING_PATH', '/config/constants.php');
  define('CONSTANTS_PATH', __DIR__ . $temporaryBasePath . CONSTANTS_ENDING_PATH);

  if (file_exists(CONSTANTS_PATH))
    require CONSTANTS_PATH;
  else
  {
    $_SERVER['APP_ENV'] = 'dev';
    require __DIR__ . '/../../../..' . CONSTANTS_ENDING_PATH;
  }

  require CONSOLE_PATH . 'colors.php';
  // @codeCoverageIgnoreEnd
}

// Generating the class map if needed
if (!file_exists(CLASS_MAP_PATH))
  require CONSOLE_PATH . 'deployment/genClassMap/genClassMapTask.php';

// loading the class map if not defined
if (!defined('CLASSMAP'))
  require CLASS_MAP_PATH;

spl_autoload_register(function(string $className) : void { require CLASSMAP[$className]; });

if (!defined('PHP_CACHE_FOLDER'))
  define ('PHP_CACHE_FOLDER', CACHE_PATH . 'php/');

/**************************************
 * HELP AND TASK CLASS MAP GENERATION *
 **************************************/

// temporarily forces to look into the development configuration
$_SERVER[APP_ENV] = 'dev';
$foldersToCheckForTasks = array_unique([CONSOLE_PATH, ...\config\AllConfig::$taskFolders ?? []]);
$_SERVER[APP_ENV] = 'prod';

$helpFileContent = [];
$taskClassMap = [];

foreach($foldersToCheckForTasks as $foldersToCheckForTask)
{
  $dir_iterator = new RecursiveDirectoryIterator($foldersToCheckForTask, FilesystemIterator::SKIP_DOTS);
  $iterator = new RecursiveIteratorIterator($dir_iterator);

  /** @var SplFileInfo $entry */
  foreach($iterator as $entry)
  {
    $pathname = $entry->getPathname();

    if (mb_strpos($pathname, 'Help.') === false)
      continue;

    $pathname = str_replace('\\', '/', $pathname);
    $consoleTask = mb_substr($pathname, mb_strrpos($pathname, '/') + 1);
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
require CONSOLE_PATH . 'tools.php';

// Generate the tasks descriptions in a cached file.
$helpFileFinalContent = '<?php return ' . var_export($helpFileContent, true);
$helpFileFinalContent = convertArrayFromVarExportToShortVersion($helpFileFinalContent) . ';' . PHP_EOL;

file_put_contents(PHP_CACHE_FOLDER . 'tasksHelp.php', $helpFileFinalContent);

// Generate the tasks paths in a cached file. We change the path in the task path that can be replaced by constants
$taskClassMap = '<?php return ' . var_export($taskClassMap, true) . ';';
$taskClassMap = convertArrayFromVarExportToShortVersion($taskClassMap);

file_put_contents(
  PHP_CACHE_FOLDER . 'tasksClassMap.php',
    str_replace("'" . BASE_PATH,
      'BASE_PATH.\'',
      str_replace("'" . CORE_PATH, 'CORE_PATH.\'', $taskClassMap)
    ) . PHP_EOL
);

if (PHP_SAPI === 'cli')
  echo CLI_GREEN, 'Generation of help and task class map done.', END_COLOR, PHP_EOL;

/********************************
 * SHELL COMPLETIONS GENERATION *
 ********************************/

// if we launch this task, the console will already launch this task before so for now, we check the variable existence
if (!defined('COMPLETIONS_SPACES_STR_PAD'))
  define('COMPLETIONS_SPACES_STR_PAD', 28);

$shellCompletionsContent = '#!/usr/bin/env bash' . PHP_EOL
. 'typeset BLC="\033[1;96m"' . PHP_EOL // CLI_BOLD_LIGHT_CYAN
. 'typeset WHI="\033[0;38m"'. PHP_EOL // CLI_WHITE
. 'typeset CYA="\033[0;36m"'. PHP_EOL //CLI_CYAN
. 'typeset ECO="\033[0m"'. PHP_EOL // END_COLOR
. 'typeset -a OTRA_COMMANDS=(' . PHP_EOL;

$taskDescription = '';

$taskCategoriesLong = $taskCategories = [];

foreach($tasks as $consoleTask)
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

  $taskDescription .= SPACE_INDENT . '"${' .  $taskCategoryLong .'} '
    . str_pad($consoleTask, COMPLETIONS_SPACES_STR_PAD) . ': ${CYA}' .
    $helpFileContent[$consoleTask][TasksManager::TASK_DESCRIPTION] . '${ECO}"'
    . PHP_EOL;
}

$shellCompletionsContent .= ');' . PHP_EOL . PHP_EOL;

foreach($taskCategories as $taskCategoryKey => $taskCategory)
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
  echo CLI_GREEN, 'Generation of shell completions script done.', END_COLOR, PHP_EOL;
