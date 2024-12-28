<?php
declare(strict_types=1);
namespace examples\deployment\fixFiles\input;
use const otra\cache\php\TEST_PATH;

class TestDynamicRequireKnownConstant
{
  public static function run(): void
  {
    require TEST_PATH . 'examples/deployment/fixFiles/input/Test2.php';
  }
}
