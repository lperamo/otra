<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture\createModel;

use const otra\cache\php\{BUNDLES_PATH, DIR_SEPARATOR};
use const otra\console\{CLI_INFO, CLI_WARNING};
use const otra\console\architecture\constants\ARG_BUNDLE_NAME;
use const otra\console\constants\DOUBLE_ERASE_SEQUENCE;
use function otra\console\promptUser;

$missingBundleErrorMessage = 'This bundle does not exist ! Try once again :';

if (!isset($argumentsVector[ARG_BUNDLE_NAME]))
{
  $bundleName = promptUser('You did not specified the name of the bundle. What is it ?');
  echo DOUBLE_ERASE_SEQUENCE;
} else
  $bundleName = $argumentsVector[ARG_BUNDLE_NAME];

if (!file_exists(BUNDLES_PATH . ucfirst($bundleName)))
{
  $bundleName = promptUser($missingBundleErrorMessage);

  // We clean the screen
  echo DOUBLE_ERASE_SEQUENCE;
}

while (!file_exists(BUNDLES_PATH . ucfirst($bundleName)))
{
  $bundleName = promptUser($missingBundleErrorMessage);

  // We clean the screen
  echo DOUBLE_ERASE_SEQUENCE;
}

// We add the chosen bundle name to the path
$bundlePath = BUNDLES_PATH . ucfirst($bundleName) . DIR_SEPARATOR;

$possibleChoices = [CREATION_MODE_FROM_NOTHING, CREATION_MODE_ONE_MODEL, CREATION_MODE_ALL_MODELS];

if (!isset($argumentsVector[ARG_METHOD]) || !in_array($argumentsVector[ARG_METHOD], $possibleChoices))
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
  $creationMode = $argumentsVector[ARG_METHOD];
