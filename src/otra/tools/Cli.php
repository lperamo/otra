<?php
/**
 * Execute a CLI command.
 *
 * @param string  $cmd     Command to pass
 *
 * @return array [int, string] Exit status code, content
 */
function cli(string $cmd) : array
{
  // We don't use 2>&1 after $cmd because there is a bug otherwise ... "The handle could not be duplicated when redirecting handle 1
  exec($cmd, $output, $return);

  return [$return, $output ? implode(PHP_EOL, $output) : ''];
}
