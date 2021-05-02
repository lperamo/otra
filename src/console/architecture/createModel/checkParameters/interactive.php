<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture\createModel;
use function otra\console\promptUser;
use const otra\console\{CLI_INFO, CLI_WARNING};

/** @var string $bundlesPath */

$missingBundleErrorMessage = 'This bundle does not exist ! Try once again :';

if (!isset($argv[ARG_BUNDLE_NAME]))
{
  $bundleName = promptUser('You did not specified the name of the bundle. What is it ?');
  echo DOUBLE_ERASE_SEQUENCE;
} else
  $bundleName = $argv[ARG_BUNDLE_NAME];

if (!file_exists($bundlesPath . ucfirst($bundleName)))
{
  $bundleName = promptUser($missingBundleErrorMessage);

  // We clean the screen
  echo DOUBLE_ERASE_SEQUENCE;
}

while (!file_exists($bundlesPath . ucfirst($bundleName)))
{
  $bundleName = promptUser($missingBundleErrorMessage);

  // We clean the screen
  echo DOUBLE_ERASE_SEQUENCE;
}

// We add the chosen bundle name to the path
$bundlePath = $bundlesPath . ucfirst($bundleName) . '/';

$possibleChoices = [CREATION_MODE_FROM_NOTHING, CREATION_MODE_ONE_MODEL, CREATION_MODE_ALL_MODELS];

if (!isset($argv[ARG_METHOD]) || !in_array($argv[ARG_METHOD], $possibleChoices))
{
  echo CLI_WARNING,
  'You did not specified how do you want to create it or this creation mode does not exist. How do you want to create it ?',
  PHP_EOL, PHP_EOL,
    CREATION_MODE_FROM_NOTHING . ' => only one model from nothing', PHP_EOL,
    CREATION_MODE_ONE_MODEL . ' => one specific model from the ', CLI_INFO, DEFAULT_BDD_SCHEMA_NAME, CLI_WARNING, PHP_EOL,
    CREATION_MODE_ALL_MODELS . ' => all from the ', CLI_INFO, DEFAULT_BDD_SCHEMA_NAME, CLI_WARNING, PHP_EOL, PHP_EOL;

  $creationMode = (int)promptUser('Your choice ?');
  $wrongMode = 'This creation mode does not exist ! Try once again :';

  // If the creation mode requested is incorrect, we ask once more until we are satisfied with the user answer
  while (!in_array($creationMode, $possibleChoices))
  {
    echo DOUBLE_ERASE_SEQUENCE;
    $creationMode = (int)promptUser($wrongMode, $wrongMode);
  }

  unset($wrongMode);

  // We clean the screen (8 lines to erase !)
  echo DOUBLE_ERASE_SEQUENCE, DOUBLE_ERASE_SEQUENCE, DOUBLE_ERASE_SEQUENCE, DOUBLE_ERASE_SEQUENCE;
} else
  $creationMode = $argv[ARG_METHOD];
