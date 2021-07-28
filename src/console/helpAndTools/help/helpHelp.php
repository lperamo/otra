<?php
declare(strict_types=1);
namespace otra\console\helpAndTools\help;
/**
 * @author Lionel Péramo
 * @package otra\console\helpAndTools
 */

use otra\console\TasksManager;

return [
  'Shows the extended help for the specified command.',
  ['command' => 'The command which you need help for.'],
  [TasksManager::REQUIRED_PARAMETER],
  'Help and tools'
];
