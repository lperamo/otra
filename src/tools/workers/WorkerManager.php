<?php
declare(strict_types=1);

namespace otra\tools\workers;

use RuntimeException;
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_INFO, CLI_INFO_HIGHLIGHT, END_COLOR, ERASE_SEQUENCE};

/**
 * @package otra\tools
 */
class WorkerManager
{
  private const array DESCRIPTOR_SPEC = [
    ['pipe', 'r'],
    ['pipe', 'w'],
    ['pipe', 'w']
  ];
  private const bool NON_BLOCKING = false;
  private const int
    PERCENTAGE_BAR_LENGTH = 50,
    STDIN = 0,
    STDOUT = 1,
    STDERR = 2,
    //BLOCKING = true,?
    OTRA_KEY_STATUS = 1;

  final public const string
    GO_UP = "\033[1A",
    ERASE_TO_END_OF_LINE = "\033[K";

  public array
    /** @var Worker[] $workers */
    $workers = [],
    /** @var array<int,string> $allMessages */
    $allMessages = [];

  private array
    /** @var resource[] $processes */
    $processes = [],
    /** @var Worker[] $runningWorkers */
    $runningWorkers = [],
    /** @var resource[]  $stdinStreams */
    $stdinStreams = [],
    /** @var resource[]  $stdoutStreams */
    $stdoutStreams = [],
    /** @var resource[]  $stderrStreams */
    $stderrStreams = [],
    /**
     * @var array<int, array> $processStatuses
     * Information about running processes.
     * The array keys are the indices of the corresponding workers in the $runningWorkers array.
     * Each value is an associative array returned by proc_get_status() that includes the following elements:
     * - command: the command string that was passed to proc_open()
     * - pid: process id
     * - running: true if the process is running, false otherwise
     * - signaled: true if the child process has been terminated by the receipt of a signal which it did not handle
     * - stopped: true if the child process has been stopped by the receipt of a signal
     * - exitcode: the exit code returned by the process (if php has been able to determine it, or -1 otherwise)
     * - termsig: the number of the signal that caused the child process to terminate its execution (only meaningful if
     * 'signaled' is true)
     * - stopsig: the number of the signal that caused the child process to stop its execution (only meaningful if
     * 'stopped' is true)
     */
    $processStatuses = [];
  private bool
    $hasPrinted = false;
  private int
    $runningWorkersCount = 0,
    $startWorkersAndSubWorkersCount = 0,
    $workersAndSubWorkersCount = 0,
    $numWorkerMessagesDisplayed = 0;
  private array $failMessages = [
    'message' => '',
    'height' => 0
  ];

  public function __construct(private readonly int $maxWorkers = 10) { }

  /**
   * @param Worker[]   $workers
   * @param resource[] $dataRead
   * @param bool       $verbose  If true, show workers waiting messages
   */
  private function handleWorkers(array $workers, array &$dataRead, bool $verbose = false): void
  {
    foreach ($workers as $workerId => $worker)
    {
      // Maybe the worker is not attached yet (surely a subworker), so we can't handle it
      if (!isset($this->runningWorkers[$workerId]))
        return;

      $dataRead[] = [
        'workerId' => $workerId,
        'stream' => $this->stdoutStreams[$workerId]
      ];
      $dataRead[] = [
        'workerId' => $worker->identifier,
        'stream' => $this->stderrStreams[$workerId]
      ];

      // we print the waiting messages if the verbosity is active
      if ($verbose && !$worker->waitingMessageDisplayed)
      {
        $this->allMessages[$workerId]['message'] = $worker->waitingMessage;
        $this->allMessages[$workerId]['height'] = $worker->waitingMessageHeight;

        echo $worker->waitingMessage . PHP_EOL;
        $this->numWorkerMessagesDisplayed += $worker->waitingMessageHeight;
        $worker->waitingMessageDisplayed = true;
      }

      // handling sub-workers messages
      $this->handleWorkers($worker->subWorkers, $dataRead, $verbose);
    }
  }

  /**
   * @param bool $verbose  false => only a progress bar,
   *                       true => display detailed information about which files are being sent
   * @param int $timeout  Microseconds to wait for stream activity before the next iteration.Used to prevent 100% CPU
   *                      usage by continuously listening to streams.
   */
  public function listen(bool $verbose = false, int $timeout = 200_000) : void
  {
    $this->startWorkersAndSubWorkersCount = $this->workersAndSubWorkersCount;

    // If the verbosity is wanted, we mask the cursor
    if (!$verbose)
      echo "\e[?25l";

    while ($this->workersAndSubWorkersCount > 0)
    {
      // If we do not have reached the 'maxWorkers' limit, then we launch the next worker
      if ($this->runningWorkersCount < $this->maxWorkers && !empty($this->workers))
      {
        /**
         * @var Worker $nextWorkerToRun
         */
        $nextWorkerToRun = array_shift($this->workers);
        $this->runningWorkers[$nextWorkerToRun->identifier]= $nextWorkerToRun;
        ++$this->runningWorkersCount;
        $process = proc_open(
          $nextWorkerToRun->command,
          self::DESCRIPTOR_SPEC,
          $pipes,
          null,
          $nextWorkerToRun->environmentVariables
        );

        $nextWorkerToRun->startTime = microtime(true);

        if (!is_resource($process))
          throw new RuntimeException('Unable to create process. Error: ' . error_get_last()['message']);

        stream_set_blocking($pipes[self::STDOUT], self::NON_BLOCKING);

        $this->processes[$nextWorkerToRun->identifier] = $process;
        $this->stdinStreams[$nextWorkerToRun->identifier] = $pipes[self::STDIN];
        $this->stdoutStreams[$nextWorkerToRun->identifier] = $pipes[self::STDOUT];
        $this->stderrStreams[$nextWorkerToRun->identifier] = $pipes[self::STDERR];

        unset($nextWorkerToRun);
      }

      // other work
      if (!$this->hasPrinted)
        $this->numWorkerMessagesDisplayed = 0;

      /** @var resource[] $dataRead */
      $dataRead = [];

      $this->handleWorkers($this->runningWorkers, $dataRead, $verbose);

      if ($verbose)
        $this->hasPrinted = true;

      $write = $except = null;
      $readStreams = array_column($dataRead, 'stream');
      $changedNum = stream_select($readStreams, $write, $except, 0, $timeout);

      if (false === $changedNum)
        throw new RuntimeException('System call interrupted by an incoming signal!');

      // If the timeout expires before anything interesting happens,
      // we can have 0 resources streams contained in the modified arrays
      if (0 === $changedNum && $this->workersAndSubWorkersCount === 0)
        return;

      foreach (array_keys($readStreams) as $index)
      {
        // Getting information from workers
        $workerId = $dataRead[$index]['workerId'];

        if (!isset($this->runningWorkers[$workerId]))
          continue;

        $worker = $this->runningWorkers[$workerId];
        $stdout = stream_get_contents($this->stdoutStreams[$workerId]);
        $stderr = stream_get_contents($this->stderrStreams[$workerId]);

        // update information about the process
        $this->processStatuses[$worker->identifier] = proc_get_status($this->processes[$worker->identifier]);

        if (!$this->processStatuses[$worker->identifier]['running'])
        {
          $exitCode = $this->detach($worker)[self::OTRA_KEY_STATUS];

          if (!$worker->aborted)
          {
            // Retrieving final messages and statuses
            if (0 === $exitCode)
            {
              $worker->done($stdout);
              $this->allMessages[$worker->identifier] = [
                'message' => $worker->successFinalMessage,
                'height' => $worker->successFinalMessageHeight
              ];
            } elseif (0 < $exitCode)
            {
              $worker->fail($stdout, $stderr, $exitCode);
              $this->failMessages['message'] .= $worker->failFinalMessage;
              $this->failMessages['height'] += $worker->failFinalMessageHeight;
              $this->allMessages[$worker->identifier]['message'] = $worker->failFinalMessage;
            } else // is this really possible?
              throw new RuntimeException();

            if ($verbose)
            {
              // we do not count $this->allMessages because $this->allMessages contains as well,
              // the sub-workers that do not have been attached yet
              if ($this->hasPrinted)
                echo str_repeat(ERASE_SEQUENCE, $this->numWorkerMessagesDisplayed);

              $this->numWorkerMessagesDisplayed = 0;

              // we print the waiting messages, the success messages and the fail messages
              foreach ($this->allMessages as $message)
              {
                echo $message['message'] . PHP_EOL;
                $this->numWorkerMessagesDisplayed += $message['height'];
              }

              echo $this->failMessages['message'];

              if ($this->failMessages['message'] !== '')
              {
                echo PHP_EOL;
                $this->numWorkerMessagesDisplayed += $this->failMessages['height'];
              }
            } else
            {
              $percentDone = round(
                100 - ($this->workersAndSubWorkersCount / $this->startWorkersAndSubWorkersCount) * 100
              );

              // Calculate the number of filled characters
              $filledLength = (int)round(self::PERCENTAGE_BAR_LENGTH * $percentDone / 100);

              // Display the progress bar
              echo "\r\033[K", 'Progress: ', CLI_INFO, str_repeat('█', $filledLength), CLI_BASE,
              str_repeat('░', self::PERCENTAGE_BAR_LENGTH - $filledLength), ' ', $percentDone, '% ';
            }
          }

          if ($this->runningWorkersCount === 0)
            break;
        }
      }

      // If there are no workers left, we consider that we have finished our job
      if ($this->workersAndSubWorkersCount === 0)
      {
        $this->hasPrinted = false;
        $this->numWorkerMessagesDisplayed = 0;
      }

      unset($stream);
    }

    // New line and showing the cursor again
    if (!$verbose)
      echo PHP_EOL, "\e[?25h";
  }

  private function countWorkersAndSubWorkers(Worker $worker) : int
  {
    // Count the actual worker
    $count = 1;

    foreach ($worker->subWorkers as $subWorker)
    {
      // Recursion to count the sub-workers
      $count += $this->countWorkersAndSubWorkers($subWorker);
    }

    return $count;
  }

  public function attach(Worker $worker, bool $incrementCounter = true) : void
  {
    $this->workers[$worker->identifier] = $worker;

    if ($incrementCounter)
      $this->workersAndSubWorkersCount += $this->countWorkersAndSubWorkers($worker);
  }

  /**
   * Cleaning memory related to a worker.
   *
   *
   * @return array{0: bool, 1: int} [bool running, int status 0 => Success, more => failure, else => abnormal]
   */
  public function detach(Worker $worker) : array
  {
    if (!isset($this->runningWorkers[$worker->identifier]) && !isset($this->workers[$worker->identifier]))
      throw new RuntimeException(
        'We do not succeed to found the worker "' . $worker->command .
        '" among the existing workers to detach it.' . implode(',', $this->workers) . '.'
      );

    if (isset($this->stdinStreams[$worker->identifier]))
    {
      fclose($this->stdinStreams[$worker->identifier]);
      fclose($this->stdoutStreams[$worker->identifier]);
      fclose($this->stderrStreams[$worker->identifier]);

      // If the process is too long, kills it.
      $elapsedTime = microtime(true) - $worker->startTime;

      if ($elapsedTime > $worker->timeout)
      {
        echo CLI_ERROR, 'The process that launched ', CLI_INFO_HIGHLIGHT, $worker->command, CLI_ERROR,
        ' was hanging during ', $elapsedTime, ' second', ($elapsedTime > 1 ? 's' : ''),
        '. We will kill the process.', END_COLOR, PHP_EOL;
        proc_terminate($this->processes[$worker->identifier]);
        $worker->aborted = true;
      }

      proc_close($this->processes[$worker->identifier]);

      // Handles workers chaining if the worker process successfully finished his job
      if (!$this->processStatuses[$worker->identifier]['running']
        && $this->processStatuses[$worker->identifier]['exitcode'] === 0
        && !empty($this->runningWorkers[$worker->identifier]->subWorkers))
      {
        // We search the first worker to chain, and we remove it from the workers list to chain
        foreach ($this->runningWorkers[$worker->identifier]->subWorkers as $subWorker)
        {
          // We convert the sub-workers into workers
          $this->attach($subWorker, false);
        }
      }

      --$this->workersAndSubWorkersCount;
      --$this->runningWorkersCount;

      unset(
        $this->runningWorkers[$worker->identifier],
        $this->processes[$worker->identifier],
        $this->stdinStreams[$worker->identifier],
        $this->stdoutStreams[$worker->identifier],
        $this->stderrStreams[$worker->identifier]
      );

      return [
        $this->processStatuses[$worker->identifier]['running'],
        $this->processStatuses[$worker->identifier]['exitcode']
      ];
    } else
    {
      unset($this->workers[$worker->identifier]);
      --$this->workersAndSubWorkersCount;

      return [false, 0];
    }
  }

  public function __destruct()
  {
    foreach($this->stdinStreams + $this->stdoutStreams + $this->stderrStreams as $stdStream)
    {
      if (is_resource($stdStream))
        fclose($stdStream);
    }

    foreach($this->processes as $process)
    {
      if (is_resource($process))
        proc_close($process);
    }
  }
}
