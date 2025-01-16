<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture\constants
{
  const
    ARG_FORCE = 5,
    ARG_INTERACTIVE = 4,
    ARG_BUNDLE_NAME = 2;
}

namespace otra\console\architecture\createBundle
{
  use otra\OtraException;

  use const otra\cache\php\{BUNDLES_PATH, CONSOLE_PATH};
  use const otra\console\
  { CLI_ERROR,
    CLI_INFO_HIGHLIGHT,
    END_COLOR,
    ERASE_SEQUENCE};
  use const otra\console\architecture\constants\{ARG_BUNDLE_NAME, ARG_FORCE, ARG_INTERACTIVE};
  use function otra\console\promptUser;
  use function otra\console\architecture\checkBooleanArgument;

  const ARG_BUNDLE_MASK = 3;

  /**
   * @param array<int, string> $argumentsVector Command-line arguments, similar to those provided by $argv.
   *
   * @throws OtraException
   * @return void
   */
  function createBundle(array $argumentsVector) : void
  {
    if (!file_exists(BUNDLES_PATH))
    {
      echo CLI_ERROR, 'There is no ', CLI_INFO_HIGHLIGHT, 'bundles', CLI_ERROR,
      ' folder to put bundles! Please create this folder or launch ', CLI_INFO_HIGHLIGHT, 'otra init', CLI_ERROR,
      ' to solve it.', END_COLOR, PHP_EOL;
      throw new OtraException(code: 1, exit: true);
    }

    require CONSOLE_PATH . 'tools.php';
    require CONSOLE_PATH . 'architecture/createBundle/createBundle.php';

    // $consoleForce determines whether we show an error when something is missing in non-interactive mode or not
    // the false value by default will stop the execution if something does not exist and show an error.
    require CONSOLE_PATH . 'architecture/checkBooleanArgument.php';
    $interactive = checkBooleanArgument($argumentsVector, ARG_INTERACTIVE, 'interactive');
    $consoleForce = checkBooleanArgument($argumentsVector, ARG_FORCE, 'force', 'false');

    // Checking argument: bundle name
    if (!isset($argumentsVector[ARG_BUNDLE_NAME]))
    {
      $bundleName = promptUser('You did not specified a name for the bundle. What is it ?');

      // We clean the screen
      echo ERASE_SEQUENCE;
    } else
      $bundleName = $argumentsVector[ARG_BUNDLE_NAME];

    bundleHandling(
      $interactive,
      $consoleForce,
      $bundleName,
      isset($argumentsVector[ARG_BUNDLE_MASK]) ? (int) $argumentsVector[ARG_BUNDLE_MASK] : null,
      true
    );
  }
}
