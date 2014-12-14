<?php

/**
 * Script_Functions : for now contains instructions for verbose mode
 * @TODO This have to work with different operating systems.
 *
 * @author lionel
 */
namespace lib\myLibs\core;

class Script_Functions
{
  /**
   * Execute a CLI command. (Passthru displays more things than system)
   *
   * @param string  $cmd     Command to pass
   * @param integer $verbose Verbose mode (0,1 or 2)
   *
   * @return bool Status
   */
  public static function cli($cmd, $verbose = 1) { return (0 < $verbose) ? passthru($cmd) : exec($cmd); }
}
?>
