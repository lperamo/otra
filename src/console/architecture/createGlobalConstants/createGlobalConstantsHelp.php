<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture\createGlobalConstants;

use const otra\console\{CLI_INFO, CLI_WARNING};

return [
  'Creates OTRA global constants. ' . CLI_WARNING .
  'Only use it if you have changed the project folder or OTRA vendor folder location.' . CLI_INFO,
  [],
  [],
  'Architecture'
];
