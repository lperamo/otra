<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\helpAndTools
 */
declare(strict_types=1);

namespace otra\console\helpAndTools\clearSession;

use const otra\cache\php\CACHE_PATH;
use const otra\console\{CLI_BASE, CLI_SUCCESS, CLI_WARNING, END_COLOR, ERASE_SEQUENCE};

function clearSession()
{
  echo CLI_BASE, 'Clearing the session data...', PHP_EOL;
  $_SESSION = [];
  array_map(unlink(...), glob(CACHE_PATH . 'php/sessions/*.*'));
  echo ERASE_SEQUENCE, 'Session data cleared', CLI_SUCCESS, ' ✔'. CLI_WARNING, PHP_EOL,
    '/!\ Do not forget to clear session cookies on client side via JavaScript or the browser development tools.',
    END_COLOR, PHP_EOL;
}
