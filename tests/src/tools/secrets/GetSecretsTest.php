<?php
declare(strict_types=1);

namespace src\tools\secrets;

use JsonException;
use PHPUnit\Framework\TestCase;
use const otra\cache\php\CORE_PATH;
use const otra\console\{CLI_ERROR, END_COLOR};
use function otra\tools\secrets\getSecrets;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class GetSecretsTest extends TestCase
{
  private const string VALID_KEY = '00112233445566778899aabbccddeeff00112233445566778899aabbccddeeff';

  protected function setUp(): void
  {
    parent::setUp();
    require_once CORE_PATH . 'tools/secrets/getSecrets.php';
  }

  /**
   * Test when the secrets file does not exist.
   *
   * Expected: an empty array is returned.
   *
   * @throws JsonException
   * @return void
   */
  public function testFileNotFound() : void
  {
    // Define SECRETS_FILE to a non-existing file.
    if (!defined('otra\tools\secrets\SECRETS_FILE'))
      define('otra\tools\secrets\SECRETS_FILE', sys_get_temp_dir() . '/non_existent_secrets.php');

    self::assertSame([], getSecrets(self::VALID_KEY, 'Test'));
  }

  /**
   * Test when the encryption key is invalid.
   *
   * Expected: error output is printed and an empty array is returned.
   *
   * @throws JsonException
   * @return void
   */
  public function testInvalidEncryptionKey() : void
  {
    // Create a temporary secrets file with valid encryption data.
    $tempFile = sys_get_temp_dir() . '/TestSecrets_' . uniqid() . '.php';

    $rawKey = hex2bin(self::VALID_KEY);
    $initializationVector = '1234567890123456'; // fixed 16-byte IV
    $encryptedKey = openssl_encrypt('testKey', 'AES-256-CBC', $rawKey, 0, $initializationVector);
    $encryptedValue = openssl_encrypt(json_encode('testValue', JSON_THROW_ON_ERROR), 'AES-256-CBC', $rawKey, 0, $initializationVector);
    $secretsData = [
      [
        'initializationVector' => bin2hex($initializationVector),
        'encryptedKey' => $encryptedKey,
        'encryptedValue' => $encryptedValue
      ]
    ];
    file_put_contents(
      $tempFile,
      '<?php' . PHP_EOL . 'return ' . var_export($secretsData, true) . ';' . PHP_EOL
    );

    if (!defined('otra\tools\secrets\SECRETS_FILE'))
      define('otra\tools\secrets\SECRETS_FILE', $tempFile);

    // Expect error output due to an invalid encryption key.
    self::expectOutputString(CLI_ERROR . 'Invalid encryption key. Expected a 32-byte key.' . END_COLOR . PHP_EOL);
    self::assertSame([], getSecrets('', 'Test'));

    // cleaning
    unlink($tempFile);
  }

  /**
   * Test when decryption fails.
   *
   * Expected: error output is printed and the secret is skipped, returning an empty array.
   *
   * @throws JsonException
   * @return void
   */
  public function testDecryptionFailure() : void
  {
    // Create a temporary secrets file with bogus encrypted data.
    $tempFile = sys_get_temp_dir() . '/TestSecrets_' . uniqid() . '.php';

    // Use a valid initialization vector (hex-encoded) but bogus encryption values.
    file_put_contents(
      $tempFile,
      '<?php' . PHP_EOL . 'return ' . var_export([
        [
          'initializationVector' => bin2hex('1234567890123456'),
          'encryptedKey' => 'bogus',
          'encryptedValue' => 'bogus'
        ]
      ], true) . ';' . PHP_EOL
    );

    if (!defined('otra\tools\secrets\SECRETS_FILE'))
      define('otra\tools\secrets\SECRETS_FILE', $tempFile);

    // Expect decryption error output.
    self::expectOutputString(CLI_ERROR . 'Decryption failed for a secret. ' . END_COLOR);
    self::assertSame([], getSecrets(self::VALID_KEY, 'Test'));
    
    // cleaning
    unlink($tempFile);
  }

  /**
   * Test the success scenario.
   *
   * Expected: the function returns the decrypted secrets.
   *
   * @throws JsonException
   * @return void
   */
  public function testGetSecretsSuccess() : void
  {
    // Create a temporary secrets file with valid encrypted data.
    $tempFile = sys_get_temp_dir() . '/TestSecrets_' . uniqid() . '.php';

    $rawKey = hex2bin(self::VALID_KEY);
    $initializationVector = '1234567890123456'; // fixed IV for deterministic result
    $secretsData = [
      [
        'initializationVector' => bin2hex($initializationVector),
        'encryptedKey' => openssl_encrypt(
          'testKey',
          'AES-256-CBC',
          $rawKey,
          0,
          $initializationVector
        ),
        'encryptedValue' => openssl_encrypt(
          json_encode('testValue', JSON_THROW_ON_ERROR),
          'AES-256-CBC',
          $rawKey,
          0,
          $initializationVector
        )
      ]
    ];
    file_put_contents(
      $tempFile,
      '<?php' . PHP_EOL . 'return ' . var_export($secretsData, true) . ';' . PHP_EOL
    );

    if (!defined('otra\tools\secrets\SECRETS_FILE'))
      define('otra\tools\secrets\SECRETS_FILE', $tempFile);

    // Expect no output on success.
    self::expectOutputString('');

    $result = getSecrets(self::VALID_KEY, 'Test');
    self::assertSame(['testKey' => 'testValue'], $result);

    unlink($tempFile);
  }
}
