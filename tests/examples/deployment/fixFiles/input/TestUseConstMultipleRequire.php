<?php
declare(strict_types=1);
namespace examples\deployment\fixFiles\input;
use const tests\examples\deployment\fixFiles\input\vendor\TEST;

class TestUseConstMultipleRequire
{
  public static function run(): void
  {
    require '/media/data/web/perso/otra/tests/examples/deployment/fixFiles/input/Test2.php';
    require '/media/data/web/perso/otra/tests/examples/deployment/fixFiles/input/vendor/const.php';
    echo TEST;
  }
}
