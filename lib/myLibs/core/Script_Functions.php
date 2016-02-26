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
   * @return bool Success or fail ?
   */
  public static function cli($cmd, $verbose = 1) { (0 < $verbose) ? passthru($cmd, $return) : exec($cmd, $return); return $return; }
}
?>
