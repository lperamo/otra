<?php declare(strict_types=1);
namespace otra\cache\php;use \Exception; use \Error; use \Throwable; use \stdClass; use \RecursiveDirectoryIterator; use \RecursiveIteratorIterator; use Phar; use \PharData; use \DateTime; use \Redis;class TestRequireClassNameConflict1
{
  public static function launch(): void{
    echo 'test';
  }
}
class TestRequireClassNameConflict
{
  public static function run(): void
  {
    
    TestRequireClassNameConflict::launch();
  }

  public static function launch(): void
  {
    echo 'world!';
  }
}
