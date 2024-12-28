<?php
declare(strict_types=1);
namespace examples\deployment\fixFiles\input;
class TestDynamicRequire
{
  private const string FOLDER = '/media/data/web/perso/otra/tests/examples/deployment/fixFiles/input/';

  public static function run(): void
  {
    require self::FOLDER . 'Test2.php';
  }
}
