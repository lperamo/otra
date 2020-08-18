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
    //BLOCKING = true,
    DESCRIPTORSPEC = [
      ['pipe', 'r'],
      ['pipe', 'w'],
      ['pipe', 'w']
    ],
    OTRA_KEY_STATUS = 1;

  public const
    ERASE_TO_END_OF_LINE = "\033[K";

  public static array $workers = [],
    $allMessages = [];

  private array
    $processes = [],
    $stdinStreams = [],
    $stdoutStreams = [],
    $stderrStreams = [];

  private static array $foundKeys = [];
  private static int $lines = 0;
  private static array $linesArray = [];
  private static array $informations = [];

  /**
   * @param Worker $worker
   */
  public function attach(Worker $worker) : void
  {
    $process = proc_open($worker->command, self::DESCRIPTORSPEC, $pipes);

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
   * @param int  $timeout
   * @param int  $verbose
   * @param bool $keepOrder
   */
  public function listen(int $timeout = 200000, int $verbose = 1, $keepOrder = true) : void
  {
    $dataRead = [];
    
    foreach (array_keys(self::$workers) as &$workerKey)
    {
      $dataRead[] = $this->stdoutStreams[$workerKey];
      $dataRead[] = $this->stderrStreams[$workerKey];
    }

    $write = $expect = null;
    $changed_num = stream_select($dataRead, $write, $expect, 0, $timeout);

    if (false === $changed_num)
      throw new RuntimeException();

    if (0 === $changed_num)
      return;

    $redDebug = false;

    foreach ($dataRead as &$stream)
    {
      // Which stream do we have to check STDOUT or STDERR ?
      /** @var int $foundKey 0 is the first worker set, 5 the fifth to have been set etc. */
      $foundKey = array_search($stream, $this->stdoutStreams, true);

      if (false === $foundKey)
      {
        $redDebug = true;
        $foundKey = array_search($stream, $this->stderrStreams, true);
        
        if (false === $foundKey)
          continue;
      }
      
      self::$foundKeys[]= $foundKey;

      // Getting information from workers
      /** @var Worker $worker */
      $worker = self::$workers[$foundKey];
      $stdout = stream_get_contents($this->stdoutStreams[$foundKey]);
      $stderr = stream_get_contents($this->stderrStreams[$foundKey]);
      $exitCode = $this->detach($worker)[self::OTRA_KEY_STATUS];

      // Retrieving final messages and statuses
      if (0 === $exitCode)
        $message = $worker->done($stdout);
      elseif (0 < $exitCode)
        $message = $worker->fail($stdout, $stderr, $exitCode);
      else // is this really possible ?
        throw new RuntimeException();

      unset($status);

      if ($verbose > 0)
      {
        if ($worker->verbose > 1)
          $message .= ' ' . $worker->command;

        // The scripts are asynchronous so if we want to keep messages in a particular order, we must move the cursor
        if ($keepOrder)
        {
          $verticalOffset = -self::$lines;
          // we move all the way to the left and we go to the right vertical position
          $offsetString = "\033[" . abs($verticalOffset) . ($verticalOffset < 0 ? "A" : "B");
          
//          if ($verticalOffset !== 0) echo $offsetString;
        }

        self::$allMessages[$foundKey] = ($redDebug ? CLI_LIGHT_BLUE : '') . $message . PHP_EOL;
        ksort(self::$allMessages);

        for ($lineIndex = 0; $lineIndex < self::$lines; ++$lineIndex)
        {
          echo self::ERASE_TO_END_OF_LINE, PHP_EOL;
        }

        // Move the cursor up "self::$lines" lines
        if ($keepOrder && $verticalOffset !== 0)
          echo "\033[" . self::$lines . "A";
          
        foreach (self::$allMessages as &$message)
        {
          echo $message;
        }
      
        // The additional 1 is to avoid to print the next message on the previous one
        self::$lines += substr_count(self::$allMessages[$foundKey], PHP_EOL);
        self::$linesArray[$foundKey] = substr_count(self::$allMessages[$foundKey], PHP_EOL) . ' ';

        if (count(self::$workers) === 0)
        {
          ksort(self::$linesArray);
//          echo implode(' ', self::$linesArray) , '***', implode(' ', self::$foundKeys);
//          echo END_COLOR;
        }
      }
    }
  }

  /**
   * Cleaning memory related to a worker.
   *
   * @param Worker $worker
   *
   * @return array [bool running, int status 0 => Success, more => failure, else => abnormal]
   */
  public function detach(Worker $worker) : array
  {
    $foundKey = array_search($worker, self::$workers, true);

    if (false === $foundKey)
      throw new RuntimeException();

    fclose($this->stdinStreams[$foundKey]);
    fclose($this->stdoutStreams[$foundKey]);
    fclose($this->stderrStreams[$foundKey]);
    // update informations about the process

    do {
      usleep(1000);
      self::$informations[$foundKey] = proc_get_status($this->processes[$foundKey]);
    } while (self::$informations[$foundKey]['running']);

    proc_close($this->processes[$foundKey]);

    unset(
      self::$workers[$foundKey],
      $this->processes[$foundKey],
      $this->stdinStreams[$foundKey],
      $this->stdoutStreams[$foundKey],
      $this->stderrStreams[$foundKey]
    );

    return [self::$informations[$foundKey]['running'], self::$informations[$foundKey]['exitcode']];
  }

  public function __destruct()
  {
    foreach($this->stdinStreams as &$stdin)
    {
      if (is_resource($stdin))
        fclose($stdin);
    }

    foreach($this->stdoutStreams as &$stdout)
    {
      if (is_resource($stdout))
        fclose($stdout);
    }

    foreach($this->stderrStreams as &$stderr)
    {
      if (is_resource($stderr))
        fclose($stderr);
    }

    foreach($this->processes as &$process)
    {
      if (is_resource($process))
        proc_close($process);
    }
  }
}
