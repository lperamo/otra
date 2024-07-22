<?php
declare(strict_types=1);
namespace examples\deployment\fixFiles\input;
use const otra\cache\php\BUNDLES_PATH;

class TestRequireRandom
{
  public static function run(): void
  {
    $externalConfigFile = BUNDLES_PATH . 'config/Config.php';

    if (file_exists($externalConfigFile))
      require $externalConfigFile;
  }
}
