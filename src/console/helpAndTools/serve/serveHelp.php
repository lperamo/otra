<?php
declare(strict_types=1);
namespace otra\console\helpAndTools\serve;
/**
 * @author Lionel Péramo
 * @package otra\console\helpAndTools
 */

use otra\console\TasksManager;

return [
  'Creates a PHP web internal server.',
  [
    'port' => 'The port used by the server ... Defaults to 8000',
    'env' => 'Environment mode [dev,prod]. Defaults to \'dev\'.'
  ],
  [
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER
  ],
  'Help and tools'
];
