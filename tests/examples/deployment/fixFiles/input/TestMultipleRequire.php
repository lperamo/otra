<?php
declare(strict_types=1);
namespace examples\deployment\fixFiles\input;
class TestMultipleRequire
{
  public static function run(): void
  {
    require '/media/data/web/perso/otra/tests/examples/deployment/fixFiles/input/vendor/Test3.php';
    require '/media/data/web/perso/otra/tests/examples/deployment/fixFiles/input/Test2.php';
  }
}
