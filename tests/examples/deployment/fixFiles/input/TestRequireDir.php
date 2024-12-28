<?php
declare(strict_types=1);
namespace examples\deployment\fixFiles\input;
class TestRequireDir
{
  public static function run(): void
  {
    require __DIR__ . 'Test2.php';
  }
}
