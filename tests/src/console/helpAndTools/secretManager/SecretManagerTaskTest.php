<?php
declare(strict_types=1);

namespace otra\console\helpAndTools\secretManager;

use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\cache\php\{BASE_PATH, CONSOLE_PATH, CORE_PATH};
use const otra\console\{CLI_BASE, CLI_INFO_HIGHLIGHT, END_COLOR, ERASE_SEQUENCE};

/**
 * @runTestsInSeparateProcesses
 */
class SecretManagerTaskTest extends TestCase
{
  private const int NO_SECRETS_POST_ERASE = 9;
  private const string
    TASK_SECRET_MANAGER = 'secretManager',
    ENVIRONMENT = 'test',
    ADD_CHOICE = '1' . PHP_EOL,
    LIST_CHOICE = '2' . PHP_EOL,
    DELETE_CHOICE = '3' . PHP_EOL,
    EXIT_CHOICE = '4' . PHP_EOL,
    COMMAND = 'php ' . BASE_PATH . 'bin/otra.php ' . self::TASK_SECRET_MANAGER . ' ' . self::ENVIRONMENT,
    EXPECTED_STTY_WARNING = 'stty: \'entrée standard\': Ioctl() inapproprié pour un périphérique' . PHP_EOL,
    KEY_ONE = 'keyOne',
    KEY_TWO = 'keyTwo',
    VALUE_ONE = 'valueOne' . PHP_EOL,
    VALUE_TWO = 'valueTwo' . PHP_EOL,
    LABEL_PRESS_ENTER_TO_CONTINUE = 'Press Enter to continue...',
    LABEL_ENTER_THE_SECRET_KEY = 'Enter the secret key: ' . 'Enter the secret value (hidden input): ' . PHP_EOL .
      PHP_EOL . self::LABEL_PRESS_ENTER_TO_CONTINUE,
    LABEL_ENTER_THE_SECRET_KEY_TO_DELETE = 'Enter the secret key to delete: 🗑️ Secret ' . CLI_INFO_HIGHLIGHT,
    LABEL_KEY_DELETED_SUCCESSFULLY = CLI_BASE . ' deleted successfully.' . PHP_EOL . PHP_EOL . self::LABEL_PRESS_ENTER_TO_CONTINUE,
    LABEL_STORED_SECRETS = '🔒 Stored Secrets:' . PHP_EOL,
    LABEL_STORED_SECRET_KEY_TWO = self::LABEL_STORED_SECRETS . '- ' . self::KEY_TWO . PHP_EOL,
    LABEL_STORED_SECRETS_BOTH_KEYS = self::LABEL_STORED_SECRETS . '- ' . self::KEY_ONE . PHP_EOL . '- ' . self::KEY_TWO . 
      PHP_EOL,
    LABEL_USING_ENVIRONMENT = CLI_INFO_HIGHLIGHT . '🔄 Using environment: TEST' . END_COLOR . PHP_EOL,
    LABEL_EXITING = '👋 Exiting...' . PHP_EOL,
    MENU = '🔐 Secret Manager (' . CLI_INFO_HIGHLIGHT . 'TEST' . CLI_BASE . ')' . PHP_EOL .
      '1. Add a new secret' . PHP_EOL .
      '2. List existing secrets' . PHP_EOL .
      '3. Delete a secret' . PHP_EOL .
      '4. Exit' . PHP_EOL .
      'Choose an option: ',
    TEST_LOG = BASE_PATH . '/testDebug.log';
  
  private readonly string $noSecrets;

  public function __construct(string $name)
  {
    parent::__construct($name);
    $this->noSecrets = 'There is no secrets.' . PHP_EOL . PHP_EOL . 'Press Enter to continue...' .
      str_repeat(ERASE_SEQUENCE, self::NO_SECRETS_POST_ERASE);
  }

  /**
   * @throws OtraException
   */
  protected function setUp(): void
  {
    parent::setUp();
    require CORE_PATH . 'tools/secrets/generateSecretsFile.php';
  }

  // Test the showSecrets() function output
  public function testShowSecrets(): void
  {
    // context
    require CONSOLE_PATH . 'helpAndTools/secretManager/secretManagerTask.php';

    $secrets = [
      self::KEY_ONE => 'valueOne',
      self::KEY_TWO => 'valueTwo'
    ];
    ob_start();
    showSecrets($secrets);
    $output = ob_get_clean();

    // Assert that the output contains the title and each secret key
    self::assertSame(
      self::LABEL_STORED_SECRETS_BOTH_KEYS,
      $output
    );
  }

  // Test the noSecrets() function output when no secret is present
  public function testNoSecrets(): void
  {
    // context
    require CONSOLE_PATH . 'helpAndTools/secretManager/secretManagerTask.php';

    // launching
    ob_start();
    noSecrets();
    $output = ob_get_clean();

    // Check that the output informs no secrets exist and prompts to continue
    self::assertSame($this->noSecrets, $output);
  }

  /**
   * @return array
   */
  private function startProcess(): array
  {
    $process = proc_open(
      self::COMMAND,
      [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
      ],
      $pipes
    );

    if (!is_resource($process)) {
      $this->logDebug('PROCESS FAILURE: Failed to start process');
      self::fail('Failed to start process');
    }

    return [$process, $pipes];
  }

  private function sendInputs(array $pipes, string ...$inputs): void
  {
    foreach ($inputs as $input)
    {
      fwrite($pipes[0], $input);
      $this->logDebug('Sent input: ' . $input);
    }

    fflush($pipes[0]);
    fclose($pipes[0]);
    $this->logDebug('Sent simulated inputs');
  }

  /**
   * @param array $pipes
   * @param       $process
   *
   * @return array
   */
  private function readOutputs(array $pipes, $process): array
  {
    foreach ([1, 2] as $index) {
      stream_set_blocking($pipes[$index], false);
    }

    $output = $stderr = '';
    $startTime = microtime(true);
    $timeout = 10; // Timeout in seconds

    while (true) {
      $status = proc_get_status($process);
      $readPipes = [$pipes[1], $pipes[2]];
      $write = $except = null;
      $streamChange = stream_select($readPipes, $write, $except, 0, 200_000);

      if ($streamChange === false) {
        $this->logDebug('STREAM_SELECT_ERROR');
        break;
      }

      foreach ($readPipes as $stream) {
        if ($stream === $pipes[1]) {
          $output .= stream_get_contents($pipes[1]);
        } elseif ($stream === $pipes[2]) {
          $stderr .= stream_get_contents($pipes[2]);
        }
      }

      $elapsedTime = microtime(true) - $startTime;
      $this->logDebug(sprintf(
        'STATUS: running=%s, exitcode=%s, time=%.2fs',
        $status['running'] ? 'true' : 'false',
        $status['exitcode'],
        $elapsedTime
      ));

      if ($elapsedTime > $timeout) {
        $this->logDebug('TIMEOUT REACHED');
        proc_terminate($process);
        self::fail("Process timed out after {$timeout} seconds");
      }

      if (!$status['running']) {
        break;
      }
    }

    $output .= stream_get_contents($pipes[1]);
    $stderr .= stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);
    $this->logDebug('FINAL OUTPUT:' . PHP_EOL . $output);

    return [$output, $stderr, $exitCode];
  }
  
  /**
   * It tests the task `secretManager` using the exit option (option "4")
   * @medium
   * @return void
   */
  public function testExitOption(): void
  {
    $this->logDebug('CMD: ' . self::COMMAND);
    [$process, $pipes] = $this->startProcess();
    $this->sendInputs($pipes, self::EXIT_CHOICE);
    [$output, $stderr, $exitCode] = $this->readOutputs($pipes, $process);

    self::assertSame('', $stderr, 'There should be no errors');
    self::assertSame(0, $exitCode, 'Unexpected exit code. STDERR: ' . $stderr);
    self::assertSame(self::LABEL_USING_ENVIRONMENT . self::MENU . self::LABEL_EXITING, $output);

    // Remove the log file if test passes
    if (file_exists(self::TEST_LOG))
      if (!unlink(self::TEST_LOG))
        $this->logDebug('Failed to remove log file');
  }

  /**
   * It tests the task `secretManager` using the exit option (option "4")
   * @medium
   * @return void
   */
  public function testListThenExit(): void
  {
    $this->logDebug('CMD: ' . self::COMMAND);
    [$process, $pipes] = $this->startProcess();
    $this->sendInputs($pipes, self::LIST_CHOICE, PHP_EOL, self::EXIT_CHOICE);
    [$output, $stderr, $exitCode] = $this->readOutputs($pipes, $process);

    self::assertSame('', $stderr, 'There should be no errors');
    self::assertSame(0, $exitCode, 'Unexpected exit code. STDERR: ' . $stderr);
    self::assertSame(
      self::LABEL_USING_ENVIRONMENT . self::MENU . $this->noSecrets . self::MENU . self::LABEL_EXITING,
      $output
    );

    // Remove the log file if test passes
    if (file_exists(self::TEST_LOG))
      if (!unlink(self::TEST_LOG))
        $this->logDebug('Failed to remove log file');
  }

  /**
   * It tests the task `secretManager` using the "add", then "list", then "exit" option (options "1", "2", "4").
   * Beware, if the test doesn't pass, we must remove the keys by deleting the related keys before relaunching the test.
   * This is to prevent "This secret already exists!" messages.
   * 
   * @medium
   * @return void
   */
  public function testAddThenListThenDeleteThenListThenExit(): void
  {
    $this->logDebug('CMD: ' . self::COMMAND);
    [$process, $pipes] = $this->startProcess();
    $this->sendInputs(
      $pipes, 
      // Add key one
      self::ADD_CHOICE,
      self::KEY_ONE . PHP_EOL,
      self::VALUE_ONE, PHP_EOL,
      // Add key two
      self::ADD_CHOICE,
      self::KEY_TWO . PHP_EOL,
      self::VALUE_TWO, PHP_EOL,
      // List
      self::LIST_CHOICE, PHP_EOL,
      // Delete key one
      self::DELETE_CHOICE,
      self::KEY_ONE . PHP_EOL . PHP_EOL,
      // Delete key two
      self::DELETE_CHOICE,
      self::KEY_TWO . PHP_EOL . PHP_EOL,
      // List
      self::LIST_CHOICE, PHP_EOL,
      // Exit
      self::EXIT_CHOICE
    );
    [$output, $stderr, $exitCode] = $this->readOutputs($pipes, $process);

    // Verifies that the only errors in stderr are the expected "stty" warnings.
    // These warnings occur because the test runs in a non-interactive environment (no TTY),
    // while the TPM secret input handling requires specific terminal capabilities.
    //
    // This is due to the need to hide secret values from being displayed in the terminal during user input.
    // As a result, `stty` fails when attempting to configure input settings
    // in an environment where no terminal is available.
    // This assertion ensures that no other unexpected errors are present during execution.
    self::assertSame(
      str_repeat(self::EXPECTED_STTY_WARNING, 4),
      $stderr, 
      'There should be no errors'
    );
    self::assertSame(0, $exitCode, 'Unexpected exit code. STDERR: ' . $stderr);
    self::assertSame(
      self::LABEL_USING_ENVIRONMENT . self::MENU . 
      self:: LABEL_ENTER_THE_SECRET_KEY . str_repeat(ERASE_SEQUENCE, 10) .
      self::MENU .
      self:: LABEL_ENTER_THE_SECRET_KEY . str_repeat(ERASE_SEQUENCE, 10) . 
      self::MENU .
      self::LABEL_STORED_SECRETS_BOTH_KEYS . PHP_EOL .
      'Press Enter to continue...' . str_repeat(ERASE_SEQUENCE, 11) .
      self::MENU . 
      self::LABEL_STORED_SECRETS_BOTH_KEYS .
      self::LABEL_ENTER_THE_SECRET_KEY_TO_DELETE . self::KEY_ONE . self::LABEL_KEY_DELETED_SUCCESSFULLY .
      str_repeat(ERASE_SEQUENCE, 13) .
      self::MENU .
      self::LABEL_STORED_SECRET_KEY_TWO .
      self::LABEL_ENTER_THE_SECRET_KEY_TO_DELETE . self::KEY_TWO . self::LABEL_KEY_DELETED_SUCCESSFULLY .
      str_repeat(ERASE_SEQUENCE, 12) .
      self::MENU . $this->noSecrets .
      self::MENU . self::LABEL_EXITING,
      $output
    );

    // Remove the log file if test passes
    if (file_exists(self::TEST_LOG))
      if (!unlink(self::TEST_LOG))
        $this->logDebug('Failed to remove log file');
  }

  private function logDebug(string $message): void
  {
    // Append debug information to a file
    file_put_contents(
      self::TEST_LOG,
      '[' . date('Y-m-d H:i:s') . "] $message\n",
      FILE_APPEND
    );
  }
}
