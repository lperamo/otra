<?php declare(strict_types=1);
namespace otra\cache\php;use \Exception; use \Error; use \Throwable; use \stdClass; use \RecursiveDirectoryIterator; use \RecursiveIteratorIterator; use Phar; use \PharData; use \DateTime; use \Redis;trait TestTrait
{
  function test(){}
}
class TestUseTrait
{
  
use TestTrait;
}
