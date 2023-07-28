<?php
/**
 * @author  Lionel Péramo
 * @package otra\tools
 */
declare(strict_types=1);

namespace otra\tools;

use JetBrains\PhpStorm\ArrayShape;
use otra\OtraException;
use const otra\console\{CLI_WARNING,END_COLOR};
use const otra\cache\php\CORE_PATH;

// If we come from the `deploy` task, those functions may already have been defined.
if (!function_exists(__NAMESPACE__ . '\\cliCommand'))
{
  define(__NAMESPACE__ . '\\OTRA_CLI_RETURN', 0);
  define(__NAMESPACE__ . '\\OTRA_CLI_OUTPUT', 1);

  /**
   * Execute a CLI command.
   *
   * @param string      $cmd Command to pass
   * @param string|null $errorMessage
   *
   * @throws OtraException
   * @return array{0: int, 1: string} Exit status code, content
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

    if ($result === false || $returnCode !== 0)
    {
      $isCli = php_sapi_name() === 'cli';

      if ($isCli && !defined(CLI_WARNING))
        require_once CORE_PATH . 'console/colors.php';

      $errorMessage = (
        $errorMessage ?? 'Problem when loading the command :' . PHP_EOL .
          ($isCli
            ? CLI_WARNING . $cmd . END_COLOR
            : $cmd
          )
        ) . PHP_EOL . 'Shell error code ' . $returnCode . '. ' . $output;

      if ($launchExceptionOnError)
        throw new OtraException($errorMessage);
      else
        echo $errorMessage . PHP_EOL;
    }

    return [$returnCode, $output];
  }

  /**
   * Executes a command with specified environment variables and returns the exit status, stdout, and stderr.
   *
   * The command is run in a separate process, and its stdout and stderr are captured.
   *
   * @param string                $command              The command to execute.
   * @param array<string, string> $environmentVariables An associative array of environment variables to set for the
   *                                                    command.
   *
   * @return array<int, mixed> Returns an array where:
   *                           - the first element is the exit status of the command (or false if the command could not
   *                             be executed),
   *                           - the second element is the output of the command,
   *                           - the third element is the error output of the command.
   */
  function runCommandWithEnvironment(string $command, array $environmentVariables): array
  {
    $process = proc_open(
      $command,
      [
        0 => ['pipe', 'r'],  // stdin is a pipe that the child will read from
        1 => ['pipe', 'w'],  // stdout is a pipe that the child will write to
        2 => ['pipe', 'w'] // stderr is a pipe that the child will write to
      ],
      $pipes,
      null,
      [
        ...$_ENV,
        ...$environmentVariables
      ]
    );

    if (is_resource($process))
    {
      $stdout = stream_get_contents($pipes[1]);
      $stderr = stream_get_contents($pipes[2]);

      fclose($pipes[0]);
      fclose($pipes[1]);
      fclose($pipes[2]);

      return [proc_close($process), $stdout, $stderr];
    }

    return [false, '', ''];
  }
}
