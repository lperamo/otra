<?php
declare(strict_types=1);

namespace src\tools\secrets;

use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\cache\php\{BASE_PATH, CORE_PATH};
use function otra\tools\secrets\getEncryptionKeyFromTPM;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class GetEncryptionKeyFromTPMTest extends TestCase
{
  private const string 
    SEAL_PRIV = BASE_PATH . 'seal.priv',
    SEAL_PRIV_OLD = BASE_PATH . 'seal.priv.old',
    SEAL_PUB = BASE_PATH . 'seal.pub',
    SEAL_PUB_OLD = BASE_PATH . 'seal.pub.old';
  
  protected function setUp(): void
  {
    // Backup key files
    if (file_exists(self::SEAL_PRIV))
      rename(self::SEAL_PRIV, self::SEAL_PRIV_OLD);

    if (file_exists(self::SEAL_PUB))
      rename(self::SEAL_PUB, self::SEAL_PUB_OLD);

    require CORE_PATH . 'tools/secrets/getEncryptionKeyFromTPM.php';
    parent::setUp();
  }
  
  protected function tearDown(): void
  {
    if (file_exists(self::SEAL_PRIV))
      unlink(self::SEAL_PRIV);

    if (file_exists(self::SEAL_PUB))
      unlink(self::SEAL_PUB);

    if (file_exists(self::SEAL_PRIV_OLD))
      rename(self::SEAL_PRIV_OLD, self::SEAL_PRIV);

    if (file_exists(self::SEAL_PUB_OLD))
    rename(self::SEAL_PUB_OLD, self::SEAL_PUB);
    parent::tearDown();
  }

  // Test when the TPM device is not found.
  // /!\ Test isn't done as it's complicated to simulate the non-existence of /dev/tpm0.

  // Test when proc_open() fails.
  // /!\ Test isn't done as it's complicated to test this as this is not a class method.

  // Test when the TPM script returns a non-zero exit code.
  // /!\ Test isn't done as it's complicated to test ....

  // Test successful extraction using the "keyedhash" pattern.
  /**
   * @medium 
   * @throws OtraException
   */
  public function testExtractKeyKeyedHash() : void
  {
    // testing and launching
    self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', getEncryptionKeyFromTPM());
  }

  // Test successful extraction using the "unsealed AES key" pattern.

  /**
   * @medium 
   * @throws OtraException
   */
  public function testExtractKeyUnsealed() : void
  {
    // context: key already present
    getEncryptionKeyFromTPM();

    // testing and launching
    self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', getEncryptionKeyFromTPM());
  }
}
