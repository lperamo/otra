<?php

use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class AllConfigTest extends TestCase
{
  protected function setUp(): void
  {
    $_SERVER['APP_ENV'] = 'prod';
  }

  /**
   * We only test here if there is no exceptions launched.
   *
   * @author Lionel Péramo
   * @doesNotPerformAssertions
   */
  public function testAllConfig_ExternalConfig() : void
  {
    // context
    $externalConfigFilePath = BASE_PATH . 'bundles/config/Config.php';
    $externalConfigFileCreated = false;

    // If there is not already an external file present then we create it temporarily
    if (file_exists($externalConfigFilePath) === false)
    {
      touch($externalConfigFilePath);
      $externalConfigFileCreated = true;
    }

    // testing
    require_once BASE_PATH . 'config/AllConfig.php';

    // cleanup if needed
    if ($externalConfigFileCreated === true)
      unlink($externalConfigFilePath);
  }
}
