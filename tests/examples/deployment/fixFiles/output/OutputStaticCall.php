<?php declare(strict_types=1);
namespace otra\cache\php;use \Exception; use \Error; use \Throwable; use \stdClass; use \RecursiveDirectoryIterator; use \RecursiveIteratorIterator; use Phar; use \PharData; use \DateTime; use \Redis;class TestStatic
{
  public static function runTest()
  {
  }
}
class TestStaticCall
{
  public static function run(): void
  {
    TestStatic::runTest();
  }
}
