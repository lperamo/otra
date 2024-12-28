<?php
declare(strict_types=1);
namespace examples\deployment\fixFiles\input;
class TestRequireComplexClass
{
  public static function run(): void
  {
    require '/media/data/web/perso/otra/tests/examples/deployment/fixFiles/input/vendor/complexClass.php';
  }
}
