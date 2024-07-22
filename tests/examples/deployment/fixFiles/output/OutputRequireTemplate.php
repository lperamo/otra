<?php declare(strict_types=1);
namespace otra\cache\php;use \Exception; use \Error; use \Throwable; use \stdClass; use \RecursiveDirectoryIterator; use \RecursiveIteratorIterator; use Phar; use \PharData; use \DateTime; use \Redis;class TestRequireTemplate
{
  public static function run(): void
  {
    ?><h1>Title</h1>
<p>A paragraph</p><?php 
  }
}
