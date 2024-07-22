<?php
declare(strict_types=1);
namespace examples\deployment\fixFiles\input;
use examples\deployment\fixFiles\input\vendor\TestRequireClassNameConflict;
class TestRequireClassNameConflict
{
  public static function run(): void
  {
    require '/media/data/web/perso/otra/tests/examples/deployment/fixFiles/input/vendor/TestRequireClassNameConflict.php';
    TestRequireClassNameConflict::launch();
  }

  public static function launch(): void
  {
    echo 'world!';
  }
}
