<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\tools\debug
 */

/**
 * Returns file and line of the caller for debugging purposes.
 *
 * @return string
 */
function getCaller() : string
{
  $secondTrace = debug_backtrace()[1];

  return $secondTrace['file'] . ':' . $secondTrace['line'];
}
