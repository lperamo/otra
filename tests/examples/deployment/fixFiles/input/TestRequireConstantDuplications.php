<?php
declare(strict_types=1);
namespace examples\deployment\fixFiles\input;
const TEST='coucou',TEST8='test8';
class TestRequireConstantDuplications
{
  public static function run(): void
  {
    require '/media/data/web/perso/otra/tests/examples/deployment/fixFiles/input/vendor/constantDuplications.php';
  }
}
