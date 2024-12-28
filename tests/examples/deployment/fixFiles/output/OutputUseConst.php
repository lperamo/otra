<?php declare(strict_types=1);
namespace otra\cache\php;use \Exception; use \Error; use \Throwable; use \stdClass; use \RecursiveDirectoryIterator; use \RecursiveIteratorIterator; use Phar; use \PharData; use \DateTime; use \Redis;const TEST=1;class TestUseConst
{
  public static function run(): void
  {
    echo TEST;
    echo TEST;
  }
}
