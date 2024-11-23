<?php
declare(strict_types=1);

namespace src\views\profiler;

use JsonException;
use PHPUnit\Framework\TestCase;
use const otra\cache\php\BASE_PATH;
use const otra\cache\php\CORE_PATH;
use const otra\src\views\profiler\{KEY_CONTAINER, KEY_CONTAINER_END};
use function otra\src\views\profiler\
{beautifyPath,
  createTabs,
  getPrettyStackTrace,
  noLogs,
  showLogs,
  showLogsFull,
  showMessageBlock,
  trimAndRemoveLastComma};

/**
 * @runTestsInSeparateProcesses
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 */
class MacrosTest extends TestCase
{
  private const string
    KEY_CONTAINER = '<i class=logs-container--message-container--label>',
    KEY_CONTAINER_END = '</i>',
    LABEL_TEST = 'TestLabel',
    LABEL_MESSAGE_PROPERTY = 'TestMessage',
    MESSAGE = '{\"a\":\"true\",\"f\":\"somePath\",\"hrc\":200,\"p\":\"/some/pattern\",\"r\":\"someRoute\",\"s\":[],\"v\":\"someVariable\"}',
    TRACE_LOGS_PATH = BASE_PATH . 'logs/dev/trace.txt';

  protected function setUp(): void
  {
    parent::setUp();
    require_once CORE_PATH . 'views/profiler/macros.phtml';
  }

  /**
   * @author Lionel Péramo
   */
  public function testShowMessageBlock() : void
  {
    // test
    self::assertSame(
      '<div>' . KEY_CONTAINER . self::LABEL_TEST . ' ' . KEY_CONTAINER_END . self::LABEL_MESSAGE_PROPERTY . '</div>',
      showMessageBlock(self::LABEL_TEST, self::LABEL_MESSAGE_PROPERTY)
    );
  }

  /**
   * @dataProvider beautifyPathProvider
   *
   * @param string $inputPath
   * @param string $expectedResult
   */
  public function testBeautifyPath(string $inputPath, string $expectedResult) : void
  {
    self::assertSame($expectedResult, beautifyPath($inputPath));
  }

  public static function beautifyPathProvider() : array
  {
    return [
      'With CORE_PATH and BASE_PATH' => [
        'CORE_PATH +BASE_PATH +',
        '<span class=requests--parameter--path>CORE_PATH +</span><span class=requests--parameter--path>BASE_PATH +</span>'
      ],
      'With only CORE_PATH' => [
        'CORE_PATH +',
        '<span class=requests--parameter--path>CORE_PATH +</span>'
      ],
      'With only BASE_PATH' => [
        'BASE_PATH +',
        '<span class=requests--parameter--path>BASE_PATH +</span>'
      ],
      'Without CORE_PATH or BASE_PATH' => [
        'somePath',
        'somePath'
      ],
    ];
  }

  /**
   * @author Lionel Péramo
   */
  public function testCreateTabs() : void
  {
    // launch
    ob_start();
    createTabs(['radio1', 'radio2'], ['tab1', 'tab2']);

    // test
    self::assertSame(
      '<input type=radio id=radio1-tab class=tabs-radios name=requests checked>' .
      '<input type=radio id=radio2-tab class=tabs-radios name=requests>' .
      '<ul class=tabs role=tablist>' .
      '<li class=tabs--item role=tab><label for=radio1-tab class=tab--item-label>tab1</label></li>' .
      '<li class=tabs--item role=tab><label for=radio2-tab class=tab--item-label>tab2</label></li>' .
      '</ul>',
      ob_get_clean()
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testNoLogs() : void
  {
    // launch
    ob_start();
    noLogs('No logs available');

    // test
    self::assertSame(
      '<div class=logs-container--item-container>No logs available</div>',
      ob_get_clean()
    );
  }

  /**
   * @dataProvider getPrettyStackTraceProvider
   *
   * @param array  $stackTrace
   * @param string $expectedOutput
   */
  public function testGetPrettyStackTrace(array $stackTrace, string $expectedOutput) : void
  {
    self::assertSame($expectedOutput, getPrettyStackTrace($stackTrace));
  }

  public static function getPrettyStackTraceProvider() : array
  {
    return [
      'Empty stack trace' => [
        [],
        'Empty.'
      ],
      'Non-empty stack trace' => [
        [
          [
            'file' => 'CORE_PATH + someFile',
            'line' => 15,
            'function' => 'someFunction',
            'args' => ['arg1', 'arg2']
          ],
          [
            'file' => 'BASE_PATH + anotherFile',
            'line' => 20,
            'function' => 'anotherFunction',
            'args' => ['arg3', 'arg4']
          ]
        ],
        '<div>' . self::KEY_CONTAINER . 'File ' . self::KEY_CONTAINER_END .
        '<span class=requests--parameter--path>CORE_PATH +</span> someFile</div>' .
        '<div>' . self::KEY_CONTAINER . 'Line ' . self::KEY_CONTAINER_END . '15</div>' .
        '<div>' . self::KEY_CONTAINER . 'Function ' . self::KEY_CONTAINER_END . 'someFunction</div>' .
        '<div>' . self::KEY_CONTAINER . 'Args ' . self::KEY_CONTAINER_END .
        "Array\n(\n    [0] => arg1\n    [1] => arg2\n)\n</div>" .
        '<hr/>' .
        '<div>' . self::KEY_CONTAINER . 'File ' . self::KEY_CONTAINER_END .
        '<span class=requests--parameter--path>BASE_PATH +</span> anotherFile</div>' .
        '<div>' . self::KEY_CONTAINER . 'Line ' . self::KEY_CONTAINER_END . '20</div>' .
        '<div>' . self::KEY_CONTAINER . 'Function ' . self::KEY_CONTAINER_END . 'anotherFunction</div>' .
        '<div>' . self::KEY_CONTAINER . 'Args ' . self::KEY_CONTAINER_END .
        "Array\n(\n    [0] => arg3\n    [1] => arg4\n)\n</div>"
      ],
    ];
  }

  /**
   * @dataProvider showLogsProvider
   *
   * @param string $errorsLogs
   * @param string $noLogsMessage
   * @param string $expectedOutput
   *
   * @throws JsonException
   */
  public function testShowLogs(string $errorsLogs, string $noLogsMessage, string $expectedOutput) : void
  {
    // context
    ob_start();

    // launch
    showLogs($errorsLogs, $noLogsMessage);

    // test
    self::assertSame(
      $expectedOutput,
      ob_get_clean()
    );
  }

  public static function showLogsProvider() : array
  {
    return [
      'Empty logs' => [
        '',
        'No logs available.',
        '<div class=logs-container--item-container>No logs available.</div>'
      ],
      'Non-empty logs' => [
        '[{"c": "1", "d": "2021-10-10", "m": "' . self::MESSAGE . '", "s": [], "i": "192.168.1.1", "u": "Mozilla/5.0"}' .
        PHP_EOL,
        'No logs available.',
        '<div class=logs-container--item-container><div><i class=logs-container--label>Date</i> 2021-10-10 00:00:00</div><div><i class=logs-container--label>CLI</i> <span class=yes-icon>✔</span></div><div><i class=logs-container--label>IP</i> 192.168.1.1</div><div class=message-container><i class=logs-container--label><b>Message</b></i><br><div><i class=logs-container--message-container--label>Ajax </i>true</div><div><i class=logs-container--message-container--label>Path </i>somePath</div><div><i class=logs-container--message-container--label>HTTP response code </i>200</div><div><i class=logs-container--message-container--label>Pattern </i>/some/pattern</div><div><i class=logs-container--message-container--label>Route </i>someRoute</div></div><div><i class=logs-container--label>Stack trace</i> Empty.</div><div><i class=logs-container--label>User Agent</i> Mozilla/5.0</div></div>'
      ]
    ];
  }

  /**
   * Tests the function trimAndRemoveLastComma
   *
   * @dataProvider trimAndRemoveLastCommaProvider
   */
  public function testTrimAndRemoveLastComma(string $inputString, string $expectedOutput): void
  {
    self::assertSame($expectedOutput, trimAndRemoveLastComma($inputString));
  }

  /**
   * Data provider for testTrimAndRemoveLastComma
   */
  public static function trimAndRemoveLastCommaProvider(): array
  {
    return [
      'No trailing comma or newline' => ['hello', 'hello'],
      'With trailing comma' => ['hello,', 'hello'],
      'With trailing newline' => ["hello\n", 'hello'],
      'With trailing comma and newline' => ["hello,\n", 'hello'],
      'Empty string' => ['', ''],
      'Only newline' => ["\n", ''],
      'Only comma' => [',', '']
    ];
  }

  /**
   * @dataProvider showLogsFullProvider
   *
   * @param string $logType
   * @param string $logFileContent
   * @param string $noLogsMessage
   * @param string $openAccordion
   * @param bool   $classic
   * @param string $expectedOutput
   *
   * @throws JsonException
   * @return void
   */
  public function testShowLogsFull(
    string $expectedOutput,
    string $logType,
    string $logFileContent,
    string $noLogsMessage,
    string $openAccordion,
    bool $classic = false
  ): void
  {
    // Context
    $logFileBackup = file_get_contents(self::TRACE_LOGS_PATH);
    file_put_contents(self::TRACE_LOGS_PATH, $logFileContent);

    ob_start();
    showLogsFull($logType, self::TRACE_LOGS_PATH, $noLogsMessage, $openAccordion, $classic);

    self::assertSame(
      $expectedOutput,
      ob_get_clean(),
      'Testing the output generated from ' . self::TRACE_LOGS_PATH
    );

    // Cleaning
    file_put_contents(self::TRACE_LOGS_PATH, $logFileBackup);
  }

  public static function showLogsFullProvider(): array
  {
    return [
      'Empty logs' => [
        '<details class=accordion><summary class=accordion>Error Logs</summary><div class=accordion--block><div class=logs-container--item-container>No logs available.</div></div></details>',
        'Error Logs',
        '',
        'No logs available.',
        ''
      ],
      'Non-empty classic logs' => [
        '<details class=accordion><summary class=accordion>Access Logs</summary><div class=accordion--block>Some log content</div></details>',
        'Access Logs',
        'Some log content',
        'No logs available.',
        '',
        true
      ],
      'Non-empty JSON logs' => [
        '<details class=accordion open><summary class=accordion>Error Logs</summary><div class=accordion--block><div class=logs-container--item-container><div><i class=logs-container--label>Date</i> 2023-01-01 00:00:00</div><div class=message-container><i class=logs-container--label><b>Message</b></i><br><div><i class=logs-container--message-container--label>Ajax </i>true</div><div><i class=logs-container--message-container--label>Path </i>somePath</div><div><i class=logs-container--message-container--label>HTTP response code </i>200</div><div><i class=logs-container--message-container--label>Pattern </i>/some/pattern</div><div><i class=logs-container--message-container--label>Route </i>someRoute</div></div></div></div></details>',
        'Error Logs',
        '[{"m": "' . self::MESSAGE . '", "d": "2023-01-01"}',
        'No logs available.',
        ' open',
      ],
    ];
  }
}
