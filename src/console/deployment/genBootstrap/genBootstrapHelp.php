<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\deployment
 */

use otra\console\TasksManager;

return [
  'Launch the genClassMap command and generates a file that contains all the necessary php files.',
  [
    'genClassmap' => 'If set to 0, it prevents the generation/override of the class mapping file.',
    'verbose' => 'If set to 1, we print all the main warnings when the task fails. Put 2 to get every warning.',
    'lint' => 'Checks for syntax errors. 0 disabled, 1 enabled (defaults to 0)',
    'route' => 'The route for which you want to generate the micro bootstrap.'
  ],
  [
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER
  ],
  'Deployment'
];
