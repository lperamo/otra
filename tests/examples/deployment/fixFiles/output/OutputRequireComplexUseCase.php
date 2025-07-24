<?php declare(strict_types=1);
namespace otra\cache\php;use \Exception; use \Error; use \Throwable; use \stdClass; use \RecursiveDirectoryIterator; use \RecursiveIteratorIterator; use Phar; use \PharData; use \DateTime; use \Redis;const VERSION='2025.1.0',RESOURCE_FILE_MIN_SIZE=21000,CACHE_TIME=300;abstract class AllConfig
{
  public static int $verbose = 0;
  public static bool $cssSourceMaps = true;
  public static string
    $cachePath = CACHE_PATH,
    $defaultConn = 'CMS',  $nodeBinariesPath = '/home/lionel/.nvm/versions/node/v20.19.1/bin/';  public static array
    $debugConfig = [
    'maxData' => 514,
    'maxDepth' => 6
  ], $deployment = [
    'domainName' => 'otra.tech',
    'server' => 'lionelp@vps812032.ovh.net',
    'port' => 49153,
    'folder' => '/var/www/html/test/',
    'privateSshKey' => '~/.ssh/id_rsa',
    'gcc' => true
  ],
    $dbConnections = [
    'CMS' => [
      'driver' => '\PDOMySQL',
      'host' => 'localhost',
      'port' => '',
      'db' => 'lpcms',
      'motor' => 'InnoDB'
    ]
  ], $sassLoadPaths = [BUNDLES_PATH . 'HelloWorld/resources/']; }class Test
{
  public function a()
  {
    /**
 * THE framework global config
 *
 * @author Lionel Péramo
 */  /** THE framework production config
 *
 * @author Lionel Péramo
 */ /**
 * @package config
 */

echo 'test';

$externalConfigFile = BUNDLES_PATH . 'config/Config.php';

if (file_exists($externalConfigFile))
  require $externalConfigFile;

if ($_SERVER[APP_ENV] === PROD && PHP_SAPI !== 'cli')
  class_alias('otra\\cache\\php\\Router', 'otra\\Router');
  }
}class TestRequireComplexUseCase
{
  public static function run(): void
  {
    
    echo 'test';
  }
}
