<?php
declare(strict_types=1);
namespace otra\tools\secrets;
use otra\OtraException;
use const otra\cache\php\{BASE_PATH, CONSOLE_PATH, CORE_PATH};
use const otra\console\{CLI_ERROR, END_COLOR};

const TPM_SCRIPT = CORE_PATH . 'tools/secrets/tpm.sh';

/**
 * Retrieve the encryption key directly from the TPM using the script.
 * If the key does not exist, it will be created automatically.
 *
 * @throws OtraException
 * @return string
 */
function getEncryptionKeyFromTPM(): string
{
  require_once CONSOLE_PATH . 'colors.php';

  if (!file_exists('/dev/tpm0'))
  {
    echo CLI_ERROR, 'TPM device not found. Ensure your TPM is enabled.', END_COLOR, PHP_EOL;
    require_once CORE_PATH . 'OtraException.php';
    throw new OtraException(code: 1, exit: true);
  }

  // Execute the TPM script
  $process = proc_open(
    'sudo ' . escapeshellcmd(TPM_SCRIPT . ' -b ' . BASE_PATH),
    [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
    $pipes
  );

  if (!is_resource($process))
  {
    echo CLI_ERROR, 'Failed to execute TPM script.', END_COLOR, PHP_EOL;
    require_once CORE_PATH . 'OtraException.php';
    throw new OtraException(code: 1, exit: true);
  }

  $output = stream_get_contents($pipes[1]);
  fclose($pipes[1]);
  $errorOutput = stream_get_contents($pipes[2]);
  fclose($pipes[2]);

  $exitCode = proc_close($process);

  if ($exitCode !== 0)
  {
    echo CLI_ERROR, 'TPM script failed:', PHP_EOL, $errorOutput, END_COLOR, PHP_EOL;
    require_once CORE_PATH . 'OtraException.php';
    throw new OtraException(code: 1, exit: true);
  }

  // Try to extract the key from 'keyedhash: ...'
  if (preg_match('@keyedhash:\s*([a-fA-F0-9]+)@', $output, $matches))
    return trim($matches[1]);

  // Try to extract the key from '🔑 Unsealed AES Key: ...'
  if (preg_match('@🔑 Unsealed AES Key:\s*([\da-fA-F\s]+)@', $output, $matches))
    return trim(str_replace([PHP_EOL, ' '], '', $matches[1])); // Remove line breaks and spaces

  echo CLI_ERROR, 'Failed to extract AES key from TPM script output.', END_COLOR, PHP_EOL;
  require_once CORE_PATH . 'OtraException.php';
  throw new OtraException(code: 1, exit: true);
}
