<?php
declare(strict_types=1);
namespace examples\deployment\fixFiles\input;
class TestDynamicRequireSimpleVariable
{
  public static function run(): void
  {
    $folder = '/media/data/web/perso/otra/tests/examples/deployment/fixFiles/input/';
    require $folder . 'Test2.php';
  }
}
