<?php
declare(strict_types=1);
namespace otra\tools\secrets;

use JsonException;
use otra\OtraException;
use Random\RandomException;
use const otra\console\{CLI_ERROR, END_COLOR};

/**
 * Generates a secure PHP file containing encrypted secrets.
 *
 * @param array<string, string> $secrets       The secrets to encrypt (both keys and values).
 * @param string                $encryptionKey The raw AES key (hexadecimal).
 * @param string                $outputFile    Path to store the generated PHP file.
 *
 * @throws JsonException|OtraException|RandomException If encryption fails or key is missing.
 */
function generateSecretsFile(array $secrets, string $encryptionKey, string $outputFile): void
{
  // Define encryption algorithm if not already defined
  if (!defined(__NAMESPACE__ . '\\CIPHER_ALGO'))
    define(__NAMESPACE__ . '\\CIPHER_ALGO', 'AES-256-CBC');

  // Convert hexadecimal AES key to raw binary format
  $rawKey = hex2bin($encryptionKey);

  if ($rawKey === false || strlen($rawKey) !== 32)
  {
    echo CLI_ERROR, 'Invalid encryption key. Expected a 32-byte key.', END_COLOR, PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }

  $encryptedSecrets = [];

  // Encrypt both keys and values using a secure random IV for each entry
  foreach ($secrets as $keyName => $value)
  {
    $initializationVector = random_bytes(16);
    $encryptedKey = openssl_encrypt($keyName, CIPHER_ALGO, $rawKey, 0, $initializationVector);
    $encryptedValue = openssl_encrypt(
      json_encode($value, JSON_THROW_ON_ERROR),
      CIPHER_ALGO,
      $rawKey,
      0, 
      $initializationVector
    );

    if ($encryptedKey === false || $encryptedValue === false)
    {
      echo CLI_ERROR, 'Encryption failed for key: ' . $keyName, END_COLOR, PHP_EOL;
      throw new OtraException(code: 1, exit: true);
    }

    // Store the initialization vector concatenated with encrypted data in binary format
    $encryptedSecrets[] = [
      'initializationVector' => bin2hex($initializationVector),
      'encryptedKey' => $encryptedKey,
      'encryptedValue' => $encryptedValue
    ];
  }

  // Generate a PHP file storing the encrypted secrets securely
  if (file_exists($outputFile))
    chmod($outputFile, 0600);

  if (file_put_contents(
      $outputFile,
      '<?php' . PHP_EOL . 'return ' . var_export($encryptedSecrets, true) . ';' . PHP_EOL,
      LOCK_EX
    ) === false)
  {
    chmod($outputFile, 0400);
    echo CLI_ERROR, 'Failed to write encrypted secrets to output file.', END_COLOR, PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }

  // Set strict file permissions (read-only for the owner)
  if (!chmod($outputFile, 0400))
  {
    echo CLI_ERROR, 'Failed to set secure permissions on output file.', END_COLOR, PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }
}
