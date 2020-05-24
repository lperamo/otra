<?php
declare(strict_types=1);

use otra\console\TasksManager;

return [
  'Returns a random hash.',
  ['rounds' => 'The numbers of round for the blowfish salt. Default: 7.'],
  [TasksManager::OPTIONAL_PARAMETER],
  'Help and tools'
];
