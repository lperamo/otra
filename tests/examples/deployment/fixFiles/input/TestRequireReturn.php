<?php
declare(strict_types=1);
namespace examples\deployment\fixFiles\input;
class TestRequireReturn
{
  public static function run(): void
  {
    $test = require '/media/data/web/perso/otra/tests/examples/deployment/fixFiles/input/vendor/TestReturn.php';
  }
}
