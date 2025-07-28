<?php
declare(strict_types=1);

namespace src\tools\secrets;

use JsonException;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use Random\RandomException;
use const otra\cache\php\{CACHE_PATH, CORE_PATH};
use const otra\console\{CLI_ERROR, END_COLOR};
use function otra\tools\secrets\generateSecretsFile;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class GenerateSecretsFileTest extends TestCase
{
  private const array SECRETS = ['testKey' => 'testValue'];
  private const string 
    SECRETS_FILE = CACHE_PATH . 'php/TestSecrets.php',
    VALID_KEY = '00112233445566778899aabbccddeeff00112233445566778899aabbccddeeff';
  
  protected function setUp(): void
  {
    require CORE_PATH . 'tools/secrets/generateSecretsFile.php';
    parent::setUp();
  }

  /**
   * @throws JsonException | OtraException | RandomException
   * @return void
   */
  public function testWrongKey() : void
  {
    self::expectException(OtraException::class);
    self::expectExceptionCode(1);
    self::expectExceptionMessage('');
    generateSecretsFile([],  '', self::SECRETS_FILE);
    self::expectOutputString(CLI_ERROR . 'Invalid encryption key. Expected a 32-byte key.' . END_COLOR . PHP_EOL);
  }

  /**
   * @throws JsonException | OtraException | RandomException
   * @return void
   */
  public function testEncryptionFailure() : void
  {
    // context
    define('otra\\tools\\secrets\\CIPHER_ALGO', 'INVALID_CIPHER');

    // test
    self::expectException(OtraException::class);
    self::expectExceptionCode(1);
    self::expectOutputString(CLI_ERROR . 'Encryption failed for key: testKey' . END_COLOR . PHP_EOL);

    // launching
    generateSecretsFile(self::SECRETS, self::VALID_KEY, self::SECRETS_FILE);
  }

  /**
   * @throws JsonException | OtraException | RandomException
   * @return void
   */
  public function testSecretsFileDoesNotExist() : void
  {
    // context
    // Create a temporary directory with no write permissions
    $tempDir = sys_get_temp_dir() . '/non_writable_dir_' . uniqid();

    // 0555: read and execute only, no write permission
    mkdir($tempDir, 0555);

    $outputFile = $tempDir . '/test_secrets.php';

    // Ensure the file does not exist
    if (file_exists($outputFile))
      unlink($outputFile);

    // test
    self::expectException(OtraException::class);
    self::expectExceptionCode(1);
    self::expectOutputString(CLI_ERROR . 'Failed to write encrypted secrets to output file.' . END_COLOR . PHP_EOL);

    // launching
    generateSecretsFile(self::SECRETS, self::VALID_KEY, $outputFile);
  }

  /**
   * @throws JsonException | RandomException
   */
  public function testChmodFailure() : void
  {
    self::expectException(OtraException::class);
    self::expectExceptionCode(1);
    self::expectOutputString(CLI_ERROR . 'Failed to set secure permissions on output file.' . END_COLOR . PHP_EOL);

    // Call generateSecretsFile, which will attempt to write to /dev/null
    // and then fail when setting secure file permissions.
    generateSecretsFile(self::SECRETS, self::VALID_KEY, '/dev/null');
  }

  /**
   * @throws JsonException | OtraException | RandomException
   */
  public function testGenerateSecretsFileSuccess() : void
  {
    // Create a temporary file for output
    $outputFile = sys_get_temp_dir() . '/TestSecrets_' . uniqid() . '.php';
    
    if (file_exists($outputFile))
      unlink($outputFile);

    // Expect no output (a success case)
    self::expectOutputString('');

    // Call generateSecretsFile with a valid secret
    generateSecretsFile(self::SECRETS, self::VALID_KEY, $outputFile);

    // Assert that the output file exists
    self::assertFileExists($outputFile);

    // Assert that file permissions are set to 0400 (read-only for the owner)
    $perms = fileperms($outputFile) & 0777;
    self::assertSame(0400, $perms);

    // Include the file and check its content
    $fileData = require $outputFile;
    self::assertIsArray($fileData);
    self::assertArrayHasKey(0, $fileData);
    self::assertArrayHasKey('initializationVector', $fileData[0]);
    self::assertArrayHasKey('encryptedKey', $fileData[0]);
    self::assertArrayHasKey('encryptedValue', $fileData[0]);

    // Cleanup: remove the temporary file
    unlink($outputFile);
  }
}
