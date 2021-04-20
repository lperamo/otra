<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\tools
 */

use JetBrains\PhpStorm\ArrayShape;

// If we come from the deploy task, those functions may already have been defined.
if (!function_exists('cliCommand'))
{
  define('OTRA_CLI_RETURN', 0);
  define('OTRA_CLI_OUTPUT', 1);

  /**
   * Execute a CLI command.
   *
   * @param string      $cmd Command to pass
   * @param string|null $errorMessage
   * @param bool        $launchExceptionOnError
   *
   * @throws \otra\OtraException
   * @return array [int, string] Exit status code, content
   */
  #[ArrayShape([
    'int',
    'string'
  ])]
  function cliCommand(string $cmd, string $errorMessage = null, bool $launchExceptionOnError = true) : array
  {
    // We don't use 2>&1 (to show errors along the output) after $cmd because there is a bug otherwise ...
    // "The handle could not be duplicated when redirecting handle 1"
    // Moreover the developer could have already used those redirections or similar things
    $result = exec($cmd, $output, $returnCode);
    $output = implode(PHP_EOL, $output);

    if (($result === false || $returnCode !== 0))
    {
      $errorMessage = ($errorMessage ?? 'Problem when loading the command :' . PHP_EOL . CLI_WARNING . $cmd .
          END_COLOR) . PHP_EOL . 'Shell error code ' . (string)$returnCode . '. ' . $output;

      if ($launchExceptionOnError)
        throw new otra\OtraException($errorMessage);
      else
        echo $errorMessage . PHP_EOL;
    }


    return [$returnCode, $output];
  }
}

