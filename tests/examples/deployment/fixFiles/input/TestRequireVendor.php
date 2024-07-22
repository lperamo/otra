<?php
declare(strict_types=1);
namespace examples\deployment\fixFiles\input;
class TestRequireVendor
{
  public static function run(): void
  {
    require '/media/data/web/perso/otra/tests/examples/deployment/fixFiles/input/vendor/Test2.php';
  }
}
