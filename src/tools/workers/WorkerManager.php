<?php
declare(strict_types=1);

namespace otra\tools\workers;

use RuntimeException;

/**
 * @package otra\tools
 */
class WorkerManager
{
  private const STDIN = 0,
    STDOUT = 1,
    STDERR = 2,
    NON_BLOCKING = false,
    //BLOCKING = true,?
    DESCRIPTOR_SPEC = [
      ['pipe', 'r'],
      ['pipe', 'w'],
      ['pipe', 'w']
    ],
    OTRA_KEY_STATUS = 1;

  public const
    ERASE_TO_END_OF_LINE = "\033[K";

  /** @var Worker[] $workers */
  public static array $workers = [],
    $allMessages = [];

  /** @var resource[] */
  private array $processes = [];

  private array
    /** @var resource[]  */
    $stdinStreams = [],
    $stdoutStreams = [],
    $stderrStreams = [];

  private static bool
    $hasStarted = false,
    $hasPrinted = false;

  private static array $informations = [];

  /**
   * @param Worker $worker
   */
  public function attach(Worker $worker) : void
  {
    $process = proc_open($worker->command, self::DESCRIPTOR_SPEC, $pipes);

    if (!is_resource($process))
      throw new RuntimeException();

    stream_set_blocking($pipes[self::STDOUT], self::NON_BLOCKING);

    self::$workers[] = $worker;
    $this->processes[] = $process;
    $this->stdinStreams[] = $pipes[self::STDIN];
    $this->stdoutStreams[] = $pipes[self::STDOUT];
    $this->stderrStreams[] = $pipes[self::STDERR];
  }

  /**
   * @param int $timeout
   * @param int $verbose
   */
  public function listen(int $timeout = 200000, int $verbose = 1) : void
  {
    /** @var resource[] $dataRead */
    $dataRead = [];
    
    foreach (self::$workers as $workerKey => &$worker)
    {
      /** @var resource */
      $dataRead[] = $this->stdoutStreams[$workerKey];
      $dataRead[] = $this->stderrStreams[$workerKey];

      if ($verbose > 0 && !self::$hasStarted)
      {
        $worker->keyInWorkersArray = $workerKey;
        self::$allMessages[$workerKey] = $worker->waitingMessage;
        echo $worker->waitingMessage . PHP_EOL;
      }
    }

    if ($verbose > 0)
      self::$hasPrinted = true;

    unset($workerKey, $worker);

    self::$hasStarted = true;

    $write = $except = null;
    $changed_num = stream_select($dataRead, $write, $except, 0, $timeout);

    // An error can happen if the system call is interrupted by an incoming signal
    if (false === $changed_num)
      throw new RuntimeException();

    // If the timeout expires before anything interesting happens,
    // we can have 0 resources streams contained in the modified arrays
    if (0 === $changed_num)
      return;

    foreach ($dataRead as $stream)
    {
      // Which stream do we have to check STDOUT or STDERR ?
      /** @var false|int|string $foundKey 0 is the first worker set, 5 the fifth to have been set etc. */
      $foundKey = array_search($stream, $this->stdoutStreams, true);

      if (false === $foundKey)
      {
        $foundKey = array_search($stream, $this->stderrStreams, true);

        if (false === $foundKey)
          continue;
      }

      // Getting information from workers
      $worker = self::$workers[$foundKey];
      $stdout = stream_get_contents($this->stdoutStreams[$foundKey]);
      $stderr = stream_get_contents($this->stderrStreams[$foundKey]);
      $exitCode = $this->detach($worker)[self::OTRA_KEY_STATUS];

      // Retrieving final messages and statuses
      if (0 === $exitCode)
        $finalMessage = $worker->done($stdout);
      elseif (0 < $exitCode)
        $finalMessage = $worker->fail($stdout, $stderr, $exitCode);
      else // is this really possible ?
        throw new RuntimeException();

      if ($verbose > 0)
      {
        if ($worker->verbose > 1)
          $finalMessage .= ' ' . $worker->command;

        if (self::$hasPrinted)
        {
          $messagesCount = count(self::$allMessages);

          for ($index = 0; $index < $messagesCount; ++$index)
          {
            echo "\033[1A" . self::ERASE_TO_END_OF_LINE;
          }

          unset($messagesCount);

        }

        self::$allMessages[$worker->keyInWorkersArray] = $finalMessage;

        foreach (self::$allMessages as $message)
          echo $message . PHP_EOL;

        unset($message);
      }
    }

    // If there are no workers left, we consider that we have finished our job
    if (count(self::$workers) === 0)
      self::$hasPrinted = self::$hasStarted = false;

    unset($stream);
  }

  /**
   * Cleaning memory related to a worker.
   *
   * @param Worker $worker
   *
   * @return array{0: bool, 1: int} [bool running, int status 0 => Success, more => failure, else => abnormal]
   */
  public function detach(Worker $worker) : array
  {
    $foundKey = array_search($worker, self::$workers, true);

    if (false === $foundKey)
      throw new RuntimeException(
        'We do not succeed to found the worker "' . $worker->command .
        '" among the existing workers in order to detach it.' . implode(',', self::$workers) . '.'
      );

    fclose($this->stdinStreams[$foundKey]);
    fclose($this->stdoutStreams[$foundKey]);
    fclose($this->stderrStreams[$foundKey]);

    $elapsedTime = 0;

    do
    {
      // We are waiting less at the start to speedup things.
      if ($elapsedTime < 10)
      {
        $elapsedTime += 1;
        usleep(100000); // .1s
      } else
      {
        $elapsedTime += 10;
        usleep(1000000); // 1s
      }

      // update informations about the process
      self::$informations[$foundKey] = proc_get_status($this->processes[$foundKey]);

      // If the process is too long, kills it.
      if ($elapsedTime > $worker->timeout * 10)
      {
        echo CLI_ERROR, 'The process that launched ', CLI_INFO_HIGHLIGHT, $worker->command, CLI_ERROR, ' was hanging during ',
          $worker->timeout, ' second', ($worker->timeout > 1 ? 's' : ''), '. We will kill the process.', END_COLOR,
          PHP_EOL;
        proc_terminate($this->processes[$foundKey]);
      }
    } while (self::$informations[$foundKey]['running']);

    proc_close($this->processes[$foundKey]);

    // Handles workers chaining
    if (!empty(self::$workers[$foundKey]->subworkers))
    {
      // We search the first worker to chain
      $firstWorkerToChain = self::$workers[$foundKey]->subworkers[0];

      // We remove it from the workers list to chain
      unset(self::$workers[$foundKey]->subworkers[0]);

      // We set the remaining workers to chain to the worker we just retrieved
      $firstWorkerToChain->subworkers = self::$workers[$foundKey]->subworkers;

      // Finally, we attach the new main worker to the WorkerManager
      $this->attach($firstWorkerToChain);
    }

    unset(
      self::$workers[$foundKey],
      $this->processes[$foundKey],
      $this->stdinStreams[$foundKey],
      $this->stdoutStreams[$foundKey],
      $this->stderrStreams[$foundKey]
    );

    return [
      self::$informations[$foundKey]['running'],
      self::$informations[$foundKey]['exitcode']
    ];
  }

  public function __destruct()
  {
    foreach($this->stdinStreams as $stdin)
    {
      if (is_resource($stdin))
        fclose($stdin);
    }

    foreach($this->stdoutStreams as $stdout)
    {
      if (is_resource($stdout))
        fclose($stdout);
    }

    foreach($this->stderrStreams as $stderr)
    {
      if (is_resource($stderr))
        fclose($stderr);
    }

    foreach($this->processes as $process)
    {
      if (is_resource($process))
        proc_close($process);
    }
  }
}
