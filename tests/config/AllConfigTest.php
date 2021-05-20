<?php
declare(strict_types=1);

namespace otra\config;

use phpunit\framework\TestCase;
use const otra\cache\php\{APP_ENV, BASE_PATH, BUNDLES_PATH, OTRA_PROJECT, PROD};

/**
 * @runTestsInSeparateProcesses
 */
class AllConfigTest extends TestCase
{
  // fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  protected function setUp(): void
  {
    parent::setUp();
    $_SERVER[APP_ENV] = PROD;
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
    $externalConfigFileFolder = BUNDLES_PATH . 'config/';

    if (!file_exists($externalConfigFileFolder))
      mkdir($externalConfigFileFolder, 0777, true);

    $externalConfigFilePath = $externalConfigFileFolder . 'Config.php';
    $externalConfigFileCreated = false;

    // If there is not already an external file present then we create it temporarily
    if (!file_exists($externalConfigFilePath))
    {
      touch($externalConfigFilePath);
      $externalConfigFileCreated = true;
    }

    // testing
    require_once BASE_PATH . 'config/AllConfig.php';

    // cleanup if needed
    if ($externalConfigFileCreated)
      unlink($externalConfigFilePath);

    // we clean the folders bundles and bundles/config only if we are working on the framework, not with.
    if (!OTRA_PROJECT)
    {
      rmdir($externalConfigFileFolder);

      if (!file_exists(BASE_PATH . 'bundles'))
        rmdir(BASE_PATH . 'bundles');
    }
  }
}
