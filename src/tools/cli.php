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
   * @param string      $command
   * @param int         $returnCode
   * @param string      $output
   * @param string|null $errorMessage
   * @param bool        $launchExceptionOnError
   *
   * @throws OtraException
   * @return void
   */
  function handleCliError(
    string $command,
    int $returnCode,
    string $output,
    ?string $errorMessage,
    bool $launchExceptionOnError
  ) :void
  {
    $isCli = php_sapi_name() === 'cli';

    if ($isCli && !defined(CLI_WARNING))
      require_once CORE_PATH . 'console/colors.php';

    $errorMessage = (
        $errorMessage ?? 'Problem when loading the command :' . PHP_EOL .
      ($isCli
        ? CLI_WARNING . $command . END_COLOR
        : $command
      )
      ) . PHP_EOL . 'Shell error code ' . $returnCode . '. ' . $output;

    if ($launchExceptionOnError)
      throw new OtraException($errorMessage);
    else
      echo $errorMessage . PHP_EOL;
  }

  /**
   * Execute a CLI command without keeping the environment.
   *
   * @param string      $command      Command to pass
   * @param string|null $errorMessage
   * @param bool        $launchExceptionOnError
   *
   * @throws OtraException
   * @return array{0: int, 1: string} Exit status code, content
   */
  #[ArrayShape([
    'int',
    'string'
  ])]
  function cliCommand(string $command, ?string $errorMessage = null, bool $launchExceptionOnError = true) : array
  {
    // "The handle could not be duplicated when redirecting handle 1"
    // Moreover the developer could have already used those redirections or similar things
    $result = exec($command . ' 2>&1', $output, $returnCode);
    $output = implode(PHP_EOL, $output);

    if ($result === false || $returnCode !== 0)
      handleCliError($command, $returnCode, $output, $errorMessage, $launchExceptionOnError);

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
   * @param string|null           $errorMessage
   * @param bool                  $launchExceptionOnError
   *
   * @throws OtraException
   * @return array<int, mixed> Returns an array where:
   *                           - the first element is the exit status of the command (or false if the command could not
   *                             be executed),
   *                           - the second element is the output of the command,
   *                           - the third element is the error output of the command.
   */
  function runCommandWithEnvironment(
    string $command,
    array $environmentVariables,
    ?string $errorMessage = null,
    bool $launchExceptionOnError = true
  ): array
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

      $returnCode = proc_close($process);

      if ($returnCode !== 0)
        handleCliError($command, $returnCode, $stdout . PHP_EOL . $stderr, $errorMessage, $launchExceptionOnError);

      return [$returnCode, $stdout, $stderr];
    }

    return [false, '', ''];
  }
}
