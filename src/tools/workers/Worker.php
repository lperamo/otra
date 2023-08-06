<?php
declare(strict_types=1);

namespace otra\tools\workers;

use const otra\console\{CLI_ERROR, END_COLOR};

/**
 * A worker is a process that can be launched in parallel with another workers, asynchronously.
 *
 * @package otra\tools
 */
class Worker
{
  public bool
    $aborted = false,
    $waitingMessageDisplayed = false;
  public float $startTime;
  public int
    $failFinalMessageHeight,
    $successFinalMessageHeight,
    $waitingMessageHeight;
  public string
    $failFinalMessage,
    $identifier,
    $successFinalMessage;

  /**
   * Worker constructor.
   *
   * @param Worker[]              $subWorkers
   * @param array<string, string> $environmentVariables An associative array of environment variables to set for the
   *                                                    command.
   */
  public function __construct(
    public string $command,
    public string $successMessage = '',
    public string $waitingMessage = 'Waiting ...',
    public ?string $failMessage = null,
    public bool $verbose = false,
    public float $timeout = 60,
    public array $subWorkers = [],
    public ?array $environmentVariables = []
  )
  {
    $this->identifier = uniqid();
    $plusCommandOnVerbose = $this->verbose < 2 ? '' : $this->command;
    $this->successFinalMessageHeight = substr_count($this->successMessage, PHP_EOL) + 1;
    $this->waitingMessageHeight = substr_count($this->waitingMessage, PHP_EOL) + 1;

    if ($this->failMessage !== null)
    {
      $this->failFinalMessage = $this->failMessage . (
        $this->failMessage !== '' &&
        $plusCommandOnVerbose !== ''
          ? ' '
          : ''
        ) . $plusCommandOnVerbose;
      $this->failFinalMessageHeight = substr_count($this->failMessage, PHP_EOL) + 1;
    }
  }

  public function done(string $stdout) : void
  {
    $this->successFinalMessage = $stdout . "\e[15;2]" . $this->successMessage;
  }

  public function fail(string $stdout, string $stderr, int $exitCode) : void
  {
    $plusCommandOnVerbose = $this->verbose < 2 ? '' : ' ' . $this->command;

    if ($this->failMessage === null)
    {
      $this->failFinalMessage = CLI_ERROR . 'Fail! ' . END_COLOR . PHP_EOL .
        'STDOUT : ' . $stdout . PHP_EOL .
        'STDERR : ' . $stderr . PHP_EOL .
        'Exit code : ' . $exitCode . $plusCommandOnVerbose;

      $this->failFinalMessageHeight = substr_count($this->failFinalMessage, PHP_EOL) + 1;
    }
  }
}
