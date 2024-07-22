<?php
declare(strict_types=1);
namespace examples\deployment\fixFiles\input;
class TestRequirePhpShortTag
{
  public static function run(): void
  {
    require '/media/data/web/perso/otra/tests/examples/deployment/fixFiles/input/vendor/shortTag.php';
  }
}
