<?php
declare(strict_types=1);
namespace otra\tools\debug;
/**
 * @author Lionel Péramo
 * @package otra\tools\debug
 *
 * Returns file and line of the caller for debugging purposes.
 *
 * @return string
 */
function getCaller() : string
{
  /** @var array{
   *    function: string,
   *    line: int,
   *    file:string,
   *    class:string,
   *    object:object,
   *    type: string,
   *    args:array
   *  } $secondTrace
   */
  $secondTrace = debug_backtrace()[1];

  return $secondTrace['file'] . ':' . $secondTrace['line'];
}
