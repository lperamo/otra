<?php

use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class AllConfigTest extends TestCase
{
  protected function setUp(): void
  {
    $_SERVER[APP_ENV] = 'prod';
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
    $externalConfigFileFolder = BASE_PATH . 'bundles/config/';

    if (file_exists($externalConfigFileFolder) === false)
      mkdir($externalConfigFileFolder, 0777, true);

    $externalConfigFilePath = $externalConfigFileFolder . 'Config.php';
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

    // we clean the folders bundles and bundles/config only if we are working on the framework, not with.
    if (OTRA_PROJECT === false)
    {
      rmdir($externalConfigFileFolder);

      if (!file_exists(BASE_PATH . 'bundles'))
        rmdir(BASE_PATH . 'bundles');
    }
  }
}
