<?php

namespace otra\tools\workers;

/**
 * @package otra\tools
 */
class Worker
{
  public string $command;
  public int $verbose;
  private string $successMessage;

  /**
   * @param string $command
   * @param string $successMessage
   * @param int    $verbosity
   */
  public function __construct(string $command, string $successMessage = '', int $verbose = 1)
  {
    $this->command = $command;
    $this->successMessage = $successMessage;
    $this->verbose = $verbose;
  }

  /**
   * @param string $offsetString
   * @param string $stdout
   * @param string $stderr
   *
   * @return string
   */
  public function done(string $stdout, string $stderr) : string
  {
    return $stdout . CLI_GREEN . "\e[15;2]" . $this->successMessage . END_COLOR;
  }

  /**
   * @param string $stdout
   * @param string $stderr
   * @param string $status
   *
   * @return string
   */
  public function fail(string $stdout, string $stderr, string $status) : string
  {
    return CLI_RED . 'Fail! The command was : "' . $this->command . '"' . END_COLOR . PHP_EOL .
      'STDOUT : ' . $stdout . PHP_EOL .
      'STDERR : ' . $stderr . PHP_EOL .
      'STATUS : ' . $status;
  }
}
