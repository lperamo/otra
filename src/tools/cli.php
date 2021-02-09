<?php
declare(strict_types=1);

use JetBrains\PhpStorm\ArrayShape;

// If we come from the deploy task, those functions may already have been defined.
if (!function_exists('cli'))
{
  define('OTRA_CLI_RETURN', 0);
  define('OTRA_CLI_OUTPUT', 1);
  /**
   * Execute a CLI command.
   *
   * @param string $cmd Command to pass
   *
   * @throws \otra\OtraException
   * @return array [int, string] Exit status code, content
   */
  #[ArrayShape([
    'int',
    'string'
  ])]
  function cli(string $cmd) : array
  {
    // We don't use 2>&1 (to show errors along the output) after $cmd because there is a bug otherwise ...
    // "The handle could not be duplicated when redirecting handle 1"
    $result = exec($cmd, $output, $return);
    $output = ($output !== null) ? implode(PHP_EOL, $output) : '';

    if ($result === false)
      throw new otra\OtraException('Problem when loading the command ' . CLI_LIGHT_YELLOW . $cmd . END_COLOR . '.' . $output);

    return [$return, $output];
  }
}

