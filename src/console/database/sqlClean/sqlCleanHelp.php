<?php
declare(strict_types=1);

use otra\console\TasksManager;

return [
  'Removes sql and yml files in the case where there are problems that had corrupted files.',
  ['cleaningLevel' => 'Type 1 in order to also remove the file that describes the tables order.'],
  [TasksManager::OPTIONAL_PARAMETER],
  'Database'
];
