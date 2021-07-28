<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\helpAndTools
 */
declare(strict_types=1);

namespace otra\console\helpAndTools\convertImages;

use otra\console\TasksManager;
use const otra\console\CLI_INFO;
use const otra\console\CLI_INFO_HIGHLIGHT;

return [
  'Converts images to another format.',
  [
    'source' => 'The source format',
    'destination' => 'The destination format',
    'quality' => 'The percentage of quality. Defaults to 75.',
    'keep' => 'Put ' . CLI_INFO_HIGHLIGHT . 'true' . CLI_INFO . ' to keep the source image as a backup. Defaults to ' .
      CLI_INFO_HIGHLIGHT . 'true' . CLI_INFO . '.'
  ],
  [
    TasksManager::REQUIRED_PARAMETER,
    TasksManager::REQUIRED_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER
  ],
  'Help and tools'
];
