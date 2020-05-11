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

/**
 * Launch one or more commands showing the output progressively
 *
 * @param string $cmd
 *
 * @return bool
 */
function cliStream(string $cmd): bool
{
  $process = proc_open(
    $cmd,
    [
      0 => ["pipe", "r"],
      1 => ["pipe", "w"],
      2 => ["pipe", "w"]
    ],
    $pipes,
    realpath('./'),
    []
  );

  if ($process === false || is_resource($process) === false)
    return false;

  while ($s = fgets($pipes[1]))
  {
    echo $s;
    flush();
  }

  return true;
}
