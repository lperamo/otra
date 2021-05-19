<?php
declare(strict_types=1);
namespace otra\console\helpAndTools\routes;
/**
 * @author Lionel Péramo
 * @package otra\console\helpAndTools
 */

use otra\console\TasksManager;

return [
  'Shows the routes and their associated kind of resources in the case they have some. (lightGreen whether they exists, red otherwise)',
  ['route' => 'The name of the route that we want information from, if we wish only one route description.'],
  [TasksManager::OPTIONAL_PARAMETER],
  'Help and tools'
];
