<?php declare(strict_types=1);
namespace otra\cache\php;use \Exception; use \Error; use \Throwable; use \stdClass; use \RecursiveDirectoryIterator; use \RecursiveIteratorIterator; use Phar; use \PharData; use \DateTime; use \Redis;class TestRequireReturn
{
  public static function run(): void
  {
    $test =  [
  'test' => '3',
  'ok' => '1'
];
  }
}