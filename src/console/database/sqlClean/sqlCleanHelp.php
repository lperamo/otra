<?php
declare(strict_types=1);
namespace otra\console\database\sqlClean;
/**
 * @author Lionel Péramo
 * @package otra\console\database
 */

use otra\console\TasksManager;

return [
  'Removes sql and yml files in the case where there are problems that had corrupted files.',
  ['cleaning-level' => 'Type 1 to also remove the file that describes the tables order.'],
  [TasksManager::OPTIONAL_PARAMETER],
  'Database'
];
