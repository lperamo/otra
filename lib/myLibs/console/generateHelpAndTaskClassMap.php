<?php

// If we do not come from the 'otra' command...
if (defined('BASE_PATH') === false)
{
  define('BASE_PATH', realpath(__DIR__ . '/../../..') . '/');  // Fixes windows awful __DIR__. The path finishes with /;
  define('CORE_PATH', BASE_PATH . 'lib/myLibs/');
  define('SPACE_INDENT', '  ');

  $pathToClassMap = BASE_PATH . 'cache/php/ClassMap.php';

  // Generating the class map if needed
  if (file_exists($pathToClassMap) === false)
    require BASE_PATH . 'lib/myLibs/console/deployment/genClassMapTask.php';

  // loading the class map
  require $pathToClassMap;
  spl_autoload_register(function(string $className) { require CLASSMAP[$className]; });

  require CORE_PATH . 'console/colors.php';
}

$dir_iterator = new \RecursiveDirectoryIterator(BASE_PATH . 'lib/myLibs/console', \FilesystemIterator::SKIP_DOTS);
$iterator = new \RecursiveIteratorIterator($dir_iterator);

$helpFileContent = [];
$taskClassMap = [];

/** @var \SplFileInfo $entry */
foreach($iterator as $entry)
{
  $pathname = $entry->getPathname();

  if (mb_strpos($pathname, 'Help.') === false || mb_strpos($pathname, 'generateHelp') !== false)
    continue;

  $task = mb_substr($pathname, mb_strrpos($pathname, '/') + 1);
  $task = mb_substr($task, 0, mb_strpos($task, 'Help'));
  $helpFileContent [$task]= require $pathname;
  $taskClassMap[$task] = [
    dirname($pathname),
    $helpFileContent[$task][\lib\myLibs\console\TasksManager::$TASK_STATUS]
  ];
}

require CORE_PATH . 'console/tools.php';

// Generate the tasks descriptions in a cached file.
$helpFileContent = '<?php return ' . var_export($helpFileContent, true);
$helpFileContent = substr(convertArrayFromVarExportToShortVersion($helpFileContent), 0, -2) . '];';

file_put_contents(BASE_PATH . 'cache/php/tasksHelp.php', $helpFileContent);

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
die;
