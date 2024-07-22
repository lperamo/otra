<?php
declare(strict_types=1);
namespace examples\deployment\fixFiles\input;

class TestRequireAllConfig
{
  public static function run(): void
  {
    require_once '/media/data/web/perso/otra/tests/examples/deployment/fixFiles/input/vendor/Config.php';
  }
}
