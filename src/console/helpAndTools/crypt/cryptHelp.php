<?php
declare(strict_types=1);

use otra\console\TasksManager;

return [
  'Crypts a password and shows it.',
  [
    'password' => 'The password to crypt.',
    'iterations' => 'The number of internal iterations to perform for the derivation.'
  ],
  [
    TasksManager::REQUIRED_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER
  ],
  'Help and tools'
];
