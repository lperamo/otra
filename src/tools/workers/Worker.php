<?php
declare(strict_types=1);

namespace otra\tools\workers;

use const otra\console\{CLI_ERROR,END_COLOR};

/**
 * A worker is a process that can be launch in parallel with another workers, asynchronously.
 *
 * @package otra\tools
 */
class Worker
{
  public int $keyInWorkersArray = -1;

  /**
   * Worker constructor.
   *
   * @param Worker[]    $subworkers
   */
  public function __construct(
    public string $command,
    public string $successMessage = '',
    public string $waitingMessage = 'Waiting ...',
    public ?string $failMessage = null,
    public int $verbose = 1,
    public int $timeout = 60,
    public array $subworkers = []
  )
  {
  }

  /**
   * @return string
   */
  public function done(string $stdout) : string
  {
    return $stdout . "\e[15;2]" . $this->successMessage;
  }

  /**
   *
   * @return string
   */
  public function fail(string $stdout, string $stderr, int $exitCode) : string
  {
    if ($this->failMessage === null)
      return CLI_ERROR . 'Fail! ' . END_COLOR . PHP_EOL .
        'STDOUT : ' . $stdout . PHP_EOL .
        'STDERR : ' . $stderr . PHP_EOL .
        'Exit code : ' . $exitCode;

    return $this->failMessage;
  }
}
