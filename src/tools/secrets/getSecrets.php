<?php
declare(strict_types=1);
namespace otra\tools\secrets;
use JsonException;
use const otra\cache\php\{CACHE_PATH, CONSOLE_PATH, DEV};
use const otra\console\{CLI_ERROR, END_COLOR};

/**
 * Loads secrets from the file if it exists.
 *
 * @throws JsonException
 * @return array The stored secrets.
 */
function getSecrets(string $encryptionKey, string $environment = DEV): array
{
  if (!defined(__NAMESPACE__ . '\\CIPHER_ALGO'))
    define(__NAMESPACE__ . '\\CIPHER_ALGO', 'AES-256-CBC');

  if (!defined(__NAMESPACE__ . '\\SECRETS_FILE'))
    define(__NAMESPACE__ . '\\SECRETS_FILE', CACHE_PATH . 'php/' . $environment . 'Secrets.php');

  if (!file_exists(SECRETS_FILE))
    return [];

  $secrets = require SECRETS_FILE;

  if ($secrets === [])
    return [];

  // Convert a hexadecimal AES key to raw binary format
  $rawKey = hex2bin($encryptionKey);

  if ($rawKey === false || strlen($rawKey) !== 32)
  {
    require_once CONSOLE_PATH . 'colors.php';
    echo CLI_ERROR, 'Invalid encryption key. Expected a 32-byte key.', END_COLOR, PHP_EOL;
    return [];
  }

  $decryptedSecrets = [];

  foreach ($secrets as $entry)
  {
    $initializationVector = hex2bin($entry['initializationVector']);
    $decryptedKey = openssl_decrypt(
      $entry['encryptedKey'],
      CIPHER_ALGO,
      $rawKey,
      0,
      $initializationVector
    );
    $decryptedValue = openssl_decrypt(
      $entry['encryptedValue'],
      CIPHER_ALGO,
      $rawKey,
      0,
      $initializationVector
    );

    if ($decryptedKey === false || $decryptedValue === false)
    {
      require_once CONSOLE_PATH . 'colors.php';
      echo CLI_ERROR, 'Decryption failed for a secret. ', END_COLOR;
      continue;
    }

    // Directly decoding the JSON value into an array
    $decryptedSecrets[$decryptedKey] = json_decode($decryptedValue, true, flags: JSON_THROW_ON_ERROR);
  }

  return $decryptedSecrets;
}
