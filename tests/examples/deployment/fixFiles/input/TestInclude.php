<?php
declare(strict_types=1);
namespace examples\deployment\fixFiles\input;
class TestInclude
{
  public static function run(): void
  {
    include '/media/data/web/perso/otra/tests/examples/deployment/fixFiles/input/Test2.php';
  }
}
