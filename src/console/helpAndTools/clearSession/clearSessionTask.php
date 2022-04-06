<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\helpAndTools
 */
declare(strict_types=1);

namespace otra\console\helpAndTools\clearSession;

use const otra\cache\php\CACHE_PATH;
use const otra\console\{CLI_BASE, ERASE_SEQUENCE, SUCCESS};

function clearSession()
{
  echo CLI_BASE, 'Clearing the session data...', PHP_EOL;
  session_unset();
  array_map(unlink(...), glob(CACHE_PATH . 'php/sessions/*.*'));
  echo ERASE_SEQUENCE, 'Session data cleared', SUCCESS;
}
