<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\deployment
 */
declare(strict_types=1);

namespace otra\console\deployment\genWatcher;

use otra\console\TasksManager;
use const otra\console\STRING_PAD_FOR_OPTION_FORMATTING;

return [
  'Launches a watcher that will update the PHP class mapping, the ts files and the scss files.',
  [
    'verbose' => '0 => Only tells that the watcher is started.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '1 => Tells which file has been updated (default).' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING .
      '2 => Tells which file has been updated and the most important events that have been triggered.' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . 'Defaults to 1.',
    'mask' => '1 => SCSS' . PHP_EOL .
    STRING_PAD_FOR_OPTION_FORMATTING . '2 => TS' . PHP_EOL .
    STRING_PAD_FOR_OPTION_FORMATTING . '4 => routes' . PHP_EOL .
    STRING_PAD_FOR_OPTION_FORMATTING . '8 => PHP (routes, configuration and class mapping)' . PHP_EOL .
    STRING_PAD_FOR_OPTION_FORMATTING . '15 => ALL. Defaults to 15.',
    'gcc' => 'Should we use Google Closure Compiler for javascript/typescript files?',
    'no SASS cache' => 'Do we have to clean the SASS/SCSS cache first? (Defaults to 0)'
  ],
  [
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER
  ],
  'Deployment'
];
