<?php declare(strict_types=1);
namespace otra\cache\php;use \Exception; use \Error; use \Throwable; use \stdClass; use \RecursiveDirectoryIterator; use \RecursiveIteratorIterator; use Phar; use \PharData; use \DateTime; use \Redis;class TestDynamicRequire
{
  private const string FOLDER = '/media/data/web/perso/otra/tests/examples/deployment/fixFiles/input/';

  public static function run(): void
  {
    require self::FOLDER . 'Test2.php';
  }
}
