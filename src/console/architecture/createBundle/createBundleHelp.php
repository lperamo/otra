<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */

declare(strict_types=1);

namespace otra\console\architecture\createBundle;

use otra\console\TasksManager;
use const otra\console\STRING_PAD_FOR_OPTION_FORMATTING;

$showMaskOption = static fn(string $text) : string => STRING_PAD_FOR_OPTION_FORMATTING . $text;

return [
  'Creates a bundle.',
  [
    'bundle-name' => 'The name of the bundle!',
    'mask' => 'In addition to the module, it will create a folder for :' . PHP_EOL .
      $showMaskOption('0 => nothing (default)') . PHP_EOL .
      $showMaskOption('1 => config') . PHP_EOL .
      $showMaskOption('2 => models') . PHP_EOL .
      $showMaskOption('4 => resources') . PHP_EOL .
      $showMaskOption('8 => views'),
    'interactive' => 'If set to false, no question will be asked but the status messages are shown. Defaults to true.',
    'force' => 'If set to true, create intermediary steps (like folders) if they are missing. Defaults to false.'
  ],
  [
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER
  ],
  'Architecture'
];
