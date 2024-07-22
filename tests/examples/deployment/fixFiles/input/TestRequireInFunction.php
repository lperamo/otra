<?php
declare(strict_types=1);
namespace examples\deployment\fixFiles\input;
class TestRequireInFunction
{
  public static function run(): void
  {
    var_dump(
      array_merge(
        [],
        require '/media/data/web/perso/otra/tests/examples/deployment/fixFiles/input/vendor/TestReturn.php'
      )
    );
  }
}
