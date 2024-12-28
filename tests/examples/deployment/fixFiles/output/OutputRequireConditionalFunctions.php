<?php declare(strict_types=1);
namespace otra\cache\php;use \Exception; use \Error; use \Throwable; use \stdClass; use \RecursiveDirectoryIterator; use \RecursiveIteratorIterator; use Phar; use \PharData; use \DateTime; use \Redis;class TestRequireConditionalFunctions
{
  public static function run(): void
  {
    if (!function_exists(__NAMESPACE__ . '\\test'))
{
  function test(int $index) : void{ }
}
  }
}
