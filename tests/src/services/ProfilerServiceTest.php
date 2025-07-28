<?php
declare(strict_types=1);

namespace src\services;

use JsonException;
use otra\OtraException;
use otra\services\ProfilerService;
use PHPUnit\Framework\TestCase;
use const otra\cache\php\{APP_ENV, CORE_PATH, DEV, PROD, TEST_PATH};

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class ProfilerServiceTest extends TestCase
{
  private const string
    LABEL_NO_STORED_QUERIES = 'No stored queries in ',
    SQL_PATH = 'examples/profilerService/',
    SQL_PATH_NO_LOG = self::SQL_PATH . 'noLog.txt',
    SQL_PATH_EMPTY_LOG = self::SQL_PATH . 'emptySqlLog.txt',
    SQL_PATH_FILLED_LOG = self::SQL_PATH . 'sqlLog.txt'; 
  
  protected function setUp(): void
  {
    parent::setUp();
    require CORE_PATH . 'services/ProfilerService.php';
  }

  /**
   * @throws OtraException
   */
  public function testSecurityCheck_Dev(): void
  {
    // context
    $_SERVER[APP_ENV] = DEV;

    // run
    ob_start();
    ProfilerService::securityCheck();

    // test
    self::assertSame('', ob_get_clean());
  }

  /**
   * @throws OtraException
   */
  public function testSecurityCheck_Prod(): void
  {
    // context
    $_SERVER[APP_ENV] = 'test';

    // run
    ob_start();
    try
    {
      ProfilerService::securityCheck();
    } catch (OtraException $exception) 
    {
      // test
      self::assertSame('No hacks.', ob_get_clean());
      self::assertSame(1, $exception->getCode());
      return;
    }

    // Si aucune exception n'est levée, le test échoue
    self::fail('Expected OtraException was not thrown.');
  }

  /**
   *
   * @dataProvider fileProvider
   *
   * @param string $filePath
   * @param string $expected
   *
   * @throws JsonException
   * @return void
   */
  public function testGetLogs(string $filePath, string $expected): void
  {
    // context
    require CORE_PATH . 'tools/translate.php';

    // run and test
    self::assertSame($expected, ProfilerService::getLogs($filePath));
  }

  /**
   * Provides data for testGetLogs.
   *
   * @return array<string, array{0: string, 1:string}>
   */
  public static function fileProvider() : array
  {
    return [
      'Non-existing SQL log' => 
      [
        TEST_PATH . self::SQL_PATH_NO_LOG,
        self::LABEL_NO_STORED_QUERIES . TEST_PATH . self::SQL_PATH_NO_LOG . '.'
      ],
      'Empty SQL log' =>
      [
        TEST_PATH . self::SQL_PATH_EMPTY_LOG,
        self::LABEL_NO_STORED_QUERIES . TEST_PATH . self::SQL_PATH_EMPTY_LOG . '.'
      ],
      'SQL log' =>
      [
        TEST_PATH . self::SQL_PATH_FILLED_LOG,
        <<<HTML
      <div class=profiler--sql-logs--element>
        <div class=profiler--sql-logs--element--left-block>
          In file <span class=profiler--sql-logs--element--file title="Click to select">bundles/CMS/backend/controllers/index/IndexAction.php</span>:<span class=profiler--sql-logs--element--line title="Click to select">31</span>&nbsp;:<div class="profiler--sql-logs--request"><span style="color:#E44">SELECT </span>* <br/><span style="color:#E44">FROM </span>lpcms_footer</div>        </div>
        <button type=button class="profiler--sql-logs--element--ripple ripple">Copy</button>
      </div>
      
HTML
      ]
    ];
  }
}
