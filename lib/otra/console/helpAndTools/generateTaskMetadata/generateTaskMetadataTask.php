<?php

// If we do not come from the 'otra' command...
use lib\otra\console\TasksManager;

if (defined('BASE_PATH') === false)
{
  define('BASE_PATH', realpath(__DIR__ . '/../../../../..') . '/');  // Fixes windows awful __DIR__. The path finishes with /;
  define('CORE_PATH', BASE_PATH . 'lib/otra/');
  define('SPACE_INDENT', '  ');

  $pathToClassMap = BASE_PATH . 'cache/php/ClassMap.php';

  // Generating the class map if needed
  if (file_exists($pathToClassMap) === false)
    require BASE_PATH . 'lib/otra/console/deployment/genClassMap/genClassMapTask.php';

  // loading the class map
  require $pathToClassMap;
  spl_autoload_register(function(string $className) { require CLASSMAP[$className]; });

  require CORE_PATH . 'console/colors.php';
}

/**************************************
 * HELP AND TASK CLASS MAP GENERATION *
 **************************************/

$dir_iterator = new \RecursiveDirectoryIterator(BASE_PATH . 'lib/otra/console', \FilesystemIterator::SKIP_DOTS);
$iterator = new \RecursiveIteratorIterator($dir_iterator);

$helpFileContent = [];
$taskClassMap = [];

/** @var \SplFileInfo $entry */
foreach($iterator as $entry)
{
  $pathname = $entry->getPathname();

  if (mb_strpos($pathname, 'Help.') === false)
    continue;

  $task = mb_substr($pathname, mb_strrpos($pathname, '/') + 1);
  $task = mb_substr($task, 0, mb_strrpos($task, 'Help'));
  $helpFileContent [$task]= require $pathname;
  $taskClassMap[$task] = [
    dirname($pathname),
    $helpFileContent[$task][TasksManager::TASK_STATUS]
  ];
}

$tasks = array_keys($helpFileContent);
$taskCategories = array_column($helpFileContent, TasksManager::TASK_CATEGORY);
// sorts alphabetically the tasks and grouping them by category
array_multisort($taskCategories, SORT_ASC, $tasks, SORT_ASC, $helpFileContent);

require CORE_PATH . 'console/tools.php';

// Generate the tasks descriptions in a cached file.
$helpFileFinalContent = '<?php return ' . var_export($helpFileContent, true);
$helpFileFinalContent = substr(convertArrayFromVarExportToShortVersion($helpFileFinalContent), 0, -2) . '];';

file_put_contents(BASE_PATH . 'cache/php/tasksHelp.php', $helpFileFinalContent);

// Generate the tasks paths in a cached file. We change the path in the task path that can be replaced by constants
$taskClassMap = '<?php return ' . var_export($taskClassMap, true);
$taskClassMap = substr(convertArrayFromVarExportToShortVersion($taskClassMap), 0, -2) . '];';
$taskClassMap = convertArrayFromVarExportToShortVersion($taskClassMap);

file_put_contents(BASE_PATH . 'cache/php/tasksClassMap.php',
    str_replace("'" . BASE_PATH,
      'BASE_PATH.\'',
      str_replace("'" . CORE_PATH, 'CORE_PATH.\'', $taskClassMap)
    )
);

if (PHP_SAPI === 'cli')
  echo CLI_GREEN, 'Generation of help and task class map done.', END_COLOR, PHP_EOL;

/********************************
 * SHELL COMPLETIONS GENERATION *
 ********************************/

define('COMPLETIONS_SPACES_STR_PAD', 28);

$shellCompletionsContent = '#!/usr/bin/env bash' . PHP_EOL
. 'typeset BLC="\033[1;96m"' . PHP_EOL // CLI_BOLD_LIGHT_CYAN
. 'typeset WHI="\033[0;38m"'. PHP_EOL // CLI_WHITE
. 'typeset CYA="\033[0;36m"'. PHP_EOL //CLI_CYAN
. 'typeset ECO="\033[0m"'. PHP_EOL // END_COLOR
. 'typeset -a OTRA_COMMANDS=(' . PHP_EOL;

$taskDescription = '';

$taskCategoriesLong = $taskCategories = [];

foreach($tasks as &$task)
{
  $shellCompletionsContent .= SPACE_INDENT . '\'' . $task . '\'' . PHP_EOL;
  $taskCategory = ucfirst($helpFileContent[$task][TasksManager::TASK_CATEGORY]);

  if (in_array($taskCategory, $taskCategories) === false) {
    $taskCategories[] = $taskCategory;
    $taskCategoryLong = 'CAT_'
      . str_replace(' ', '_', strtoupper($helpFileContent[$task][TasksManager::TASK_CATEGORY]));
    $taskCategoriesLong[] = $taskCategoryLong;
  }

  $taskDescription .= SPACE_INDENT . '"${' .  $taskCategoryLong .'} '
    . str_pad($task, COMPLETIONS_SPACES_STR_PAD) . ': ${CYA}' . $helpFileContent[$task][TasksManager::TASK_DESCRIPTION] . '${ECO}"'
    . PHP_EOL;
}

$shellCompletionsContent .= ');' . PHP_EOL . PHP_EOL;

foreach($taskCategories as $key => &$taskCategory)
{
  $shellCompletionsContent .= 'typeset ' . $taskCategoriesLong[$key] . '="${BLC}[ '
    . str_pad($taskCategory, strlen('Help and tools'), ' ', STR_PAD_BOTH) . ' ]${WHI}";' . PHP_EOL;
}

$shellCompletionsContent .= PHP_EOL . 'typeset -a OTRA_COMMANDS_DESCRIPTIONS=(' . PHP_EOL
  . $taskDescription . ')' . PHP_EOL . PHP_EOL
  . 'export OTRA_COMMANDS' . PHP_EOL . 'export OTRA_COMMANDS_DESCRIPTIONS' . PHP_EOL;

file_put_contents(CORE_PATH . 'console/shellCompletions/shellCompletions.sh', $shellCompletionsContent);

if (PHP_SAPI === 'cli')
  echo CLI_GREEN, 'Generation of shell completions script done.', END_COLOR, PHP_EOL;
