<?php
declare(strict_types=1);
namespace examples\deployment\fixFiles\input;
class TestRequireOnce
{
  public static function run(): void
  {
    require_once '/media/data/web/perso/otra/tests/examples/deployment/fixFiles/input/Test2.php';
  }
}
