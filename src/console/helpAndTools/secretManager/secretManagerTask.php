<?php
declare(strict_types=1);
namespace otra\console\helpAndTools\secretManager;

/**
 * Interactive CLI script to securely manage encrypted secrets using TPM.
 *
 * @author  Lionel Péramo
 * @package otra\console\helpAndTools
 */

use JetBrains\PhpStorm\NoReturn;
use JsonException;
use otra\OtraException;
use Random\RandomException;
use RuntimeException;
use const otra\cache\php\{CACHE_PATH, CORE_PATH, DEV};
use const otra\console\{CLI_BASE, CLI_INFO_HIGHLIGHT, CLI_WARNING, END_COLOR, ERASE_SEQUENCE};
use function otra\tools\secrets\{getEncryptionKeyFromTPM, generateSecretsFile, getSecrets};

const
  SECRET_MANAGER_ARG_ENV = 2, // Position of the environment argument
  ARG_ADD = '1',
  ARG_LIST = '2',
  ARG_DELETE = '3',
  ARG_EXIT = '4',
  MENU_SIZE = 6,
  ERASE_LINES_AFTER_ADD = MENU_SIZE + 4,
  ERASE_LINES_AFTER_ADD_EMPTY_KEY = MENU_SIZE + 2,
  ERASE_LINES_WITH_SECRETS = MENU_SIZE + 3,
  ERASE_LINES_AFTER_DELETE = ERASE_LINES_WITH_SECRETS + 3,
  ERASE_LINES_DEFAULTS = MENU_SIZE + 3,
  LABEL_PRESS_ANY_KEY = 'Press Enter to continue...';

/**
 * Interactive menu for managing secrets.
 *
 * @param array<int, string> $argumentsVector Command-line arguments, similar to those provided by $argv.
 *
 * @throws JsonException|OtraException|RandomException
 * @return void
 */
#[NoReturn] function secretManager(array $argumentsVector): void
{
  if (!defined('otra\\tools\secrets\\CIPHER_ALGO'))
    define('otra\\tools\secrets\\CIPHER_ALGO', 'AES-256-CBC');

  // Determine the environment, defaulting to DEV
  $environment = isset($argumentsVector[SECRET_MANAGER_ARG_ENV])
    ? strtolower($argumentsVector[SECRET_MANAGER_ARG_ENV])
    : DEV;

  define(__NAMESPACE__ . '\\SECRETS_FILE', CACHE_PATH . 'php/' . $environment . 'Secrets.php');
  echo CLI_INFO_HIGHLIGHT, '🔄 Using environment: ', strtoupper($environment), END_COLOR, PHP_EOL;

  // Retrieve the encryption key from the TPM
  require CORE_PATH . 'tools/secrets/getEncryptionKeyFromTPM.php';
  $encryptionKey = getEncryptionKeyFromTPM();

  while (true)
  {
    echo '🔐 Secret Manager (', CLI_INFO_HIGHLIGHT, strtoupper($environment), CLI_BASE, ')', PHP_EOL;
    echo '1. Add a new secret', PHP_EOL;
    echo '2. List existing secrets', PHP_EOL;
    echo '3. Delete a secret', PHP_EOL;
    echo '4. Exit', PHP_EOL;
    echo 'Choose an option: ';

    $input = fgets(STDIN);

    if ($input === false)
      throw new RuntimeException('Unexpected end of input stream');

    switch (trim($input))
    {
      case ARG_ADD:
        require_once CORE_PATH . '/tools/secrets/getSecrets.php';
        $secrets = getSecrets($encryptionKey, $environment);
        echo 'Enter the secret key: ';
        $secretKey = trim(fgets(STDIN));

        if ($secretKey === '')
        {
          echo CLI_WARNING, '⚠️ Secret key cannot be empty.', CLI_BASE, PHP_EOL;
          echo str_repeat(ERASE_SEQUENCE, ERASE_LINES_AFTER_ADD_EMPTY_KEY);
          break;
        }

        $oneLineMoreToErase = false;

        if (isset($secrets[$secretKey]))
        {
          echo CLI_WARNING, '⚠️ This secret already exists! Overwriting...', CLI_BASE, PHP_EOL;
          $oneLineMoreToErase = true;
        }

        echo 'Enter the secret value (hidden input): ';

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
          $input = trim(shell_exec(
            'powershell -Command Read-Host -AsSecureString | ConvertFrom-SecureString -AsPlainText'
          ));
        else
        {
          system('stty -echo');
          $input = trim(fgets(STDIN));
          system('stty echo');
          echo PHP_EOL;
        }

        $secrets[$secretKey] = $input;
        require_once CORE_PATH . 'tools/secrets/generateSecretsFile.php';
        generateSecretsFile($secrets, $encryptionKey, SECRETS_FILE);
        echo PHP_EOL, LABEL_PRESS_ANY_KEY;
        fgets(STDIN);
        echo str_repeat(ERASE_SEQUENCE, ERASE_LINES_AFTER_ADD + ($oneLineMoreToErase ? 1 : 0));
        break;

      case ARG_LIST:
        require_once CORE_PATH . '/tools/secrets/getSecrets.php';
        $secrets = getSecrets($encryptionKey, $environment);

        if ($secrets === [])
        {
          noSecrets();
          break;
        }

        showSecrets($secrets);
        echo PHP_EOL, LABEL_PRESS_ANY_KEY;
        fgets(STDIN);
        echo str_repeat(ERASE_SEQUENCE, ERASE_LINES_WITH_SECRETS + count($secrets));
        break;

      case ARG_DELETE:
        require_once CORE_PATH . '/tools/secrets/getSecrets.php';
        $secrets = getSecrets($encryptionKey, $environment);

        if ($secrets === [])
        {
          noSecrets();
          break;
        }

        showSecrets($secrets);
        echo 'Enter the secret key to delete: ';
        $keyToDelete = trim(fgets(STDIN));

        if (isset($secrets[$keyToDelete]))
        {
          unset($secrets[$keyToDelete]);
          require_once CORE_PATH . 'tools/secrets/generateSecretsFile.php';
          generateSecretsFile($secrets, $encryptionKey, SECRETS_FILE);
          echo '🗑️ Secret ', CLI_INFO_HIGHLIGHT, $keyToDelete, CLI_BASE, ' deleted successfully.', PHP_EOL;
        } else
          echo '⚠️ Secret ', CLI_INFO_HIGHLIGHT, $keyToDelete, CLI_BASE, ' not found.', PHP_EOL;

        echo PHP_EOL, LABEL_PRESS_ANY_KEY;
        fgets(STDIN);
        echo str_repeat(ERASE_SEQUENCE, ERASE_LINES_AFTER_DELETE + count($secrets));
        break;

      case ARG_EXIT:
        echo '👋 Exiting...', PHP_EOL;
        throw new OtraException(code: 0, exit: true);

      default:
        echo CLI_WARNING, '⚠️ Invalid option. Please choose again.', CLI_BASE, PHP_EOL;
        echo PHP_EOL, LABEL_PRESS_ANY_KEY;
        fgets(STDIN);
        echo str_repeat(ERASE_SEQUENCE, ERASE_LINES_DEFAULTS);
    }
  }
}

function noSecrets():void
{
  echo 'There is no secrets.', PHP_EOL, PHP_EOL, LABEL_PRESS_ANY_KEY;
  fgets(STDIN);
  echo str_repeat(ERASE_SEQUENCE, MENU_SIZE + 3);
}

/**
 * @param array<string, string> $secrets
 *
 * @return void
 */
function showSecrets(array $secrets): void
{
  echo '🔒 Stored Secrets:', PHP_EOL;

  foreach (array_keys($secrets) as $secretKey)
    echo '- ', $secretKey, PHP_EOL;
}
