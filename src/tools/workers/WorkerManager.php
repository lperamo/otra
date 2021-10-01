<?php
declare(strict_types=1);

namespace otra\tools\workers;

use RuntimeException;
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR};

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
    GO_UP = "\033[1A",
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

  private static int $workersThatHaveBeenAttached = 0;
  private static string $failMessages = '';
  private static array $informations = [];

  /**
   * @param int $timeout
   * @param int $verbose
   */
  public function listen(int $timeout = 200000, int $verbose = 1) : void
  {
    if (!self::$hasPrinted)
      self::$workersThatHaveBeenAttached = 0;

    /** @var resource[] $dataRead */
    $dataRead = [];

    $workerOrderKey = array_key_first(self::$workers);

    foreach (self::$workers as &$worker)
    {
      // Maybe the worker is not attached yet (surely a subworker)
      if (!isset(self::$workers[$workerOrderKey]))
        continue;

      /** @var resource */
      $dataRead[] = $this->stdoutStreams[$workerOrderKey];
      $dataRead[] = $this->stderrStreams[$workerOrderKey];

      // we print the waiting messages if the verbosity is active
      if ($verbose > 0 && !self::$hasStarted)
      {
        $worker->keyInWorkersArray = $workerOrderKey;
        self::$allMessages[$workerOrderKey] = $worker->waitingMessage;

        // handling subworkers messages
        foreach ($worker->subworkers as $subworker)
        {
          ++$workerOrderKey;
          $subworker->keyInWorkersArray = $workerOrderKey;
          self::$allMessages[$workerOrderKey] = $subworker->waitingMessage;
        }

        echo $worker->waitingMessage . PHP_EOL;
        self::$workersThatHaveBeenAttached += substr_count($worker->waitingMessage, PHP_EOL) + 1;
      }

      ++$workerOrderKey;
    }

    if ($verbose > 0)
      self::$hasPrinted = true;

    unset($workerOrderKey, $worker, $subworker);

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
      // Which stream do we have to check STDOUT or STDERR?
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
      {
        self::$failMessages .= $worker->fail($stdout, $stderr, $exitCode);
        $finalMessage = '';
      }
      else // is this really possible?
        throw new RuntimeException();

      if ($verbose > 0)
      {
        if ($worker->verbose > 1)
        {
          if ($finalMessage !== '')
            $finalMessage .= ' ';

          $finalMessage .= $worker->command;
        }

        if (self::$hasPrinted)
        {
          // we do not count self::$allMessages because self::$allMessages contains as well,
          // the subworkers that do not have been attached yet
          $messagesCount = self::$workersThatHaveBeenAttached;

          for ($index = 0; $index < $messagesCount; ++$index)
          {
            echo self::GO_UP . self::ERASE_TO_END_OF_LINE;
          }

          unset($messagesCount);
        }

        self::$allMessages[$worker->keyInWorkersArray] = $finalMessage;
        self::$workersThatHaveBeenAttached = 0;

        // we print the waiting messages, the success messages and the fail messages
        foreach (self::$allMessages as $message)
        {
          echo $message . PHP_EOL;
          self::$workersThatHaveBeenAttached += substr_count($message, PHP_EOL) + 1;
        }

        echo self::$failMessages;

        if (self::$failMessages !== '')
        {
          echo PHP_EOL;
          self::$workersThatHaveBeenAttached += substr_count(self::$failMessages, PHP_EOL) + 1;
        }

        unset($message);
      }
    }

    // If there are no workers left, we consider that we have finished our job
    if (count(self::$workers) === 0)
    {
      self::$hasPrinted = self::$hasStarted = false;
      self::$workersThatHaveBeenAttached = 0;
    }

    unset($stream);
  }

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

      // update information about the process
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

    // Handles workers chaining if the worker process finished successfully his job
    if (!self::$informations[$foundKey]['running']
      && self::$informations[$foundKey]['exitcode'] === 0
      && !empty(self::$workers[$foundKey]->subworkers))
    {
      // We search the first worker to chain, and we remove it from the workers list to chain
      foreach(self::$workers[$foundKey]->subworkers as $subworker)
      {
        // We convert the subworkers into workers
        $this->attach($subworker);
      }
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
