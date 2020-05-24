<?php
declare(strict_types=1);

use otra\console\TasksManager;

return [
  'Generates a class mapping file that will be used to replace the autoloading method.',
  ['verbose' => 'If set to 1 => Show all the classes that will be used. Default to 0.'],
  [TasksManager::OPTIONAL_PARAMETER],
  'Deployment',
  __FILE__
];
