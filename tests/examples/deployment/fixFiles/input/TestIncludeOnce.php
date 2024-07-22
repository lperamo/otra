<?php
declare(strict_types=1);
namespace examples\deployment\fixFiles\input;
class TestIncludeOnce
{
  public static function run(): void
  {
    include_once '/media/data/web/perso/otra/tests/examples/deployment/fixFiles/input/Test2.php';
  }
}
