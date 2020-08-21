<?php
declare(strict_types=1);

namespace otra\tools\workers;

/**
 * @package otra\tools
 */
class Worker
{
  public int
    $verbose,
    $timeout,
    $keyInWorkersArray = -1;

  public string
    $command,
    $waitingMessage;

  private string $successMessage;

  /**
   * @param string $command
   * @param string $successMessage
   * @param string $waitingMessage
   * @param int    $verbose
   * @param int    $timeout
   */
  public function __construct(
    string $command,
    string $successMessage = '',
    string $waitingMessage = 'Waiting ...',
    int $verbose = 1,
    int $timeout = 60)
  {
    $this->command = $command;
    $this->successMessage = $successMessage;
    $this->waitingMessage = $waitingMessage;
    $this->verbose = $verbose;
    $this->timeout = $timeout;
  }

  /**
   * @param string $stdout
   *
   * @return string
   */
  public function done(string $stdout) : string
  {
    return $stdout . "\e[15;2]" . $this->successMessage;
  }

  /**
   * @param string $stdout
   * @param string $stderr
   * @param int    $exitCode
   *
   * @return string
   */
  public function fail(string $stdout, string $stderr, int $exitCode) : string
  {
    return CLI_RED . 'Fail! The command was : "' . $this->command . '"' . END_COLOR . PHP_EOL .
      'STDOUT : ' . $stdout . PHP_EOL .
      'STDERR : ' . $stderr . PHP_EOL .
      'Exit code : ' . $exitCode;
  }
}
