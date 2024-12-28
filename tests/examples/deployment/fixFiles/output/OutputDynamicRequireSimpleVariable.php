<?php declare(strict_types=1);
namespace otra\cache\php;use \Exception; use \Error; use \Throwable; use \stdClass; use \RecursiveDirectoryIterator; use \RecursiveIteratorIterator; use Phar; use \PharData; use \DateTime; use \Redis;class TestDynamicRequireSimpleVariable
{
  public static function run(): void
  {
    $folder = '/media/data/web/perso/otra/tests/examples/deployment/fixFiles/input/';
    require $folder . 'Test2.php';
  }
}
