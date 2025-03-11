<?php
declare(strict_types=1);
namespace src\tools\secrets;

use JsonException;
use otra\OtraException;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Redis;
use const otra\cache\php\BASE_PATH;
use const otra\cache\php\CORE_PATH;
use function otra\tools\secrets\getEncryptionKeyFromTPMDaemon;

/**
 * @runTestsInSeparateProcesses
 */
#[CoversFunction('otra\tools\secrets\getEncryptionKeyFromTPMDaemon')]
class GetEncryptionKeyFromTPMDaemonTest extends TestCase
{
  private const string SOCKET_PATH = '/run/otra/tpm_daemon.sock';
  private static ?Redis $redis = null;

  private static function getRedis(): Redis
  {
    if (self::$redis === null) 
    {
      self::$redis = new Redis();
      self::$redis->pconnect('127.0.0.1');
    }

    try
    {
      // The PING command is the canonical way to check if a connection is alive.
      // It will throw an exception if the connection is closed.
      self::$redis->ping('+OK');
    } catch (RedisException $exception)
    {
      // If ping fails, the connection is dead. Reconnect.
      self::$redis->pconnect('127.0.0.1');
    }

    return self::$redis;
  }
  
  protected function setUp(): void
  {
    self::$redis = self::getRedis();
    
    if (self::$redis->exists('tpm'))
      self::$redis->del('tpm');

    // Checking the socket
    if (!file_exists(self::SOCKET_PATH))
    {
      // If we have a backup, we restore it
      if (file_exists(self::SOCKET_PATH . '.bak'))
        rename(self::SOCKET_PATH . '.bak', self::SOCKET_PATH);
      else
        self::markTestSkipped('TPM daemon socket not found. Ensure the daemon ' . self::SOCKET_PATH . ' is running.');
    }

    require_once CORE_PATH . 'tools/secrets/getEncryptionKeyFromTPMDaemon.php';

    parent::setUp();
  }

  /**
   * @medium
   * @throws JsonException|OtraException
   */
  public function testItReturnsDecryptedKey(): void
  {
    $response = getEncryptionKeyFromTPMDaemon();
    self::assertMatchesRegularExpression(
      '/^[a-f0-9]{64}$/i', $response, '❌ Invalid encryption key format.' . print_r($response, true)
    );
  }

  /**
   * @medium
   * @throws OtraException|JsonException
   */
  public function testItThrowsExceptionWhenDaemonIsDown(): void
  {
    try
    {
      // context
      $testSocketPath = sys_get_temp_dir() . '/test_tpm_daemon_' . uniqid() . '.sock';

      // testing
      $this->expectException(OtraException::class);
      $this->expectExceptionMessageMatches('@Could not connect to TPM daemon:@');
      getEncryptionKeyFromTPMDaemon($testSocketPath);
    } finally
    {
      if (file_exists($testSocketPath))
        unlink($testSocketPath);

      if (self::$redis !== null)
      {
        if (self::$redis->exists('tpm'))
          self::$redis->del('tpm');

        self::$redis->close();
      }
    }
  }

  /**
   * @medium
   * @throws OtraException|JsonException
   */
  public function testItThrowsExceptionOnMalformedJson(): void
  {
    // 1. Contexte : On crée notre propre socket dans un répertoire sûr et temporaire
    $testSocketPath = sys_get_temp_dir() . '/test_tpm_daemon_' . uniqid() . '.sock';
    
    $server = stream_socket_server(
      'unix://' . $testSocketPath, 
      $errno,
      $errorString
    );

    if (!$server)
      self::fail("Failed to create test socket server: $errorString ($errno)");

    $processId = pcntl_fork();

    if ($processId === -1)
      self::fail('Could not fork process for test.');

    if ($processId === 0)
    {
      $connection = stream_socket_accept($server);

      // Simulate invalid JSON response
      if ($connection)
      {
        fwrite($connection, '{invalid_json:');
        fclose($connection);
      }

      fwrite(STDERR, 'test');
      exit;
    }

    try
    {
      $this->expectException(OtraException::class);
      $this->expectExceptionMessageMatches('@JSON decoding error:@');
      getEncryptionKeyFromTPMDaemon($testSocketPath);
    } finally
    {
      pcntl_wait($status); // Wait the end of the child

      // The parent is now solely responsible for cleaning up the server
      fclose($server);

      if (file_exists($testSocketPath))
        unlink($testSocketPath);
    }
  }
}
