<?php
declare(strict_types=1);
namespace examples\deployment\fixFiles\input;
use function examples\deployment\fixFiles\input\vendor\runTest;

class TestUseFunction
{
  public static function run(): void
  {
    require '/media/data/web/perso/otra/tests/examples/deployment/fixFiles/input/vendor/Test5.php';
    runTest();
  }
}
