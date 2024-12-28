<?php declare(strict_types=1);
namespace otra\cache\php;use \Exception; use \Error; use \Throwable; use \stdClass; use \RecursiveDirectoryIterator; use \RecursiveIteratorIterator; use Phar; use \PharData; use \DateTime; use \Redis;class Test2
{
  public static function noop(): void {}
}

class Test3
{
  public static function noop(): void {}
}
class TestInlineTwoUse
{
  public static function run(): void
  {
    Test2::noop();
    Test3::noop();
  }
}
