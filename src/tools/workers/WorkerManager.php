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
  ];

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

        if ($foundKey !== false)
        {
//          if (in_array($foundKey, self::$foundKeys))
//          {
//            var_dump('ERRRRRROOOOORRR!');die;
//          } else {
//            var_dump('****', array_keys(self::$foundKeys), $foundKey, '+++');
//          }
        }
        
        if (false === $foundKey)
          continue;
      }
      
      self::$foundKeys[]= $foundKey;

      // Getting information from workers
      $worker = self::$workers[$foundKey];
      $stdout = stream_get_contents($this->stdoutStreams[$foundKey]);
      $stderr = stream_get_contents($this->stderrStreams[$foundKey]);
      $status = $this->detach($worker);

      // Retrieving final messages and statuses
      if (0 === $status)
        $message = $worker->done($stdout, $stderr);
      elseif (0 < $status)
        $message = $worker->fail($stdout, $stderr, $status);
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
          
          if ($verticalOffset !== 0) echo $offsetString;
        }

        self::$allMessages[$foundKey] = ($redDebug ? CLI_LIGHT_BLUE : '') . $foundKey . $stdout . $stderr . $message . PHP_EOL;
        ksort(self::$allMessages);

        for ($lineIndex = 0; $lineIndex < self::$lines; ++$lineIndex)
        {
          echo "\033[K", PHP_EOL;
        }
        
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
          echo implode(' ', self::$linesArray) , '***', implode(' ', self::$foundKeys);
          echo END_COLOR;
        }
      }
    }
  }

  /**
   * Cleaning memory related to a worker.
   *
   * @param Worker $worker
   *
   * @return int 0 => Success, more => failure, else => abnormal
   */
  public function detach(Worker $worker) : int
  {
    $foundKey = array_search($worker, self::$workers, true);

    if (false === $foundKey)
      throw new RuntimeException();

    fclose($this->stdinStreams[$foundKey]);
    fclose($this->stdoutStreams[$foundKey]);
    fclose($this->stderrStreams[$foundKey]);
    $status = proc_close($this->processes[$foundKey]);

    unset(
      self::$workers[$foundKey],
      $this->processes[$foundKey],
      $this->stdinStreams[$foundKey],
      $this->stdoutStreams[$foundKey],
      $this->stderrStreams[$foundKey]
    );

    return $status;
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
