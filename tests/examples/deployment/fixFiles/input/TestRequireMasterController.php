<?php
declare(strict_types=1);
namespace examples\deployment\fixFiles\input;
class TestRequireMasterController
{
  public static function run(): void
  {
    require '/media/data/web/perso/otra/src/MasterController.php';
  }
}
