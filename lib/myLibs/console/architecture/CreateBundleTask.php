<?
declare(strict_types=1);
require CORE_PATH . 'console/Tools.php';
require CORE_PATH . 'console/architecture/CreateBundle.php';

const ARG_BUNDLE_NAME = 2,
  ARG_BUNDLE_MASK = 3;

// Checking argument : bundle name
if (false === isset($argv[ARG_BUNDLE_NAME]))
{
  $bundleName = promptUser('You did not specified a name for the bundle. What is it ?');

  // We clean the screen
  echo ERASE_SEQUENCE;
} else {
  $bundleName = $argv[ARG_BUNDLE_NAME];
}

bundleHandling($bundleName, $argv[ARG_BUNDLE_MASK] ?? null, true);
?>
