<?php
declare(strict_types=1);
namespace otra\console\deployment\buildDev;
/**
 * @author Lionel Péramo
 * @package otra\console\deployment
 */

use otra\console\TasksManager;
use const otra\console\{CLI_INFO, CLI_INFO_HIGHLIGHT};

return [
  'Compiles the typescripts, sass and php configuration files (modulo the binary mask).',
  [
    'verbose' => '0 => Quite silent, 1 => Tells which file has been updated.',
    'mask' => '1 => SCSS, 2 => TS, ..., 4 => routes, ..., 8 => PHP, 15 => ALL. Default to 15.',
    'gcc' => 'Should we use Google Closure Compiler for javascript/typescript files ? Defaults to ' . 
      CLI_INFO_HIGHLIGHT . 'false' . CLI_INFO . '.',
    'scope' => '0 => project files (default), 1 => OTRA files, 2 => All the files',
    'route' => 'Specify the route whose assets should be compiled. Defaults to ' . CLI_INFO_HIGHLIGHT . '_all' . 
      CLI_INFO . '.'
  ],
  [
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER
  ],
  'Deployment'
];
