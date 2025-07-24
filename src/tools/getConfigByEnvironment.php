<?php
declare(strict_types=1);
namespace otra\tools;

use otra\OtraException;
use Random\RandomException;
use ReflectionClass;
use const otra\cache\php\{BASE_PATH, CORE_PATH};
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_INFO_HIGHLIGHT};
use const otra\console\deployment\deploy\DEPLOY_ENVIRONMENT;

/**
 * @param string       $environment
 * @param list<string> $wantedProperties
 * @param bool         $mandatory
 *
 * @throws OtraException|RandomException
 * @return array
 */
function getConfigByEnvironment(string $environment = 'prod', array $wantedProperties = [], bool $mandatory = true) : array
{
  // Retrieving the good config according to the passed environment 
  $configPath = BASE_PATH . 'config/' . $environment . '/AllConfig.php';

  if (!is_file($configPath))
  {
    echo CLI_ERROR, 'Unknown environment: ', CLI_INFO_HIGHLIGHT, $environment, CLI_BASE, PHP_EOL;
    require_once CORE_PATH . 'OtraException.php';
    throw new OtraException(code: 1, exit: true);
  }

  // Patch namespace to avoid redeclaration
  $namespace = '_Tmp' . bin2hex(random_bytes(4));
  $patched  = preg_replace(
    [
      '/^\s*<\?php\s*/',
      '/namespace\s+[^;]+;/'],
    ['', "namespace $namespace;"],
    file_get_contents($configPath),
    1
  );

  if ($patched === null)
    throw new RuntimeException('Namespace patch failed');

  eval($patched); // defines \_TmpXXXX\AllConfig
  $class = "\\$namespace\\AllConfig";

  // 4. Reflection only for the two wanted properties
  $refClass = new ReflectionClass($class);
  $config = [];

  foreach ($wantedProperties as $name)
  {
    if (!$refClass->hasProperty($name))
    {
      if ($mandatory)
      {
        echo CLI_ERROR, 'Property ', CLI_INFO_HIGHLIGHT, $name, CLI_ERROR, ' not found in ', CLI_INFO_HIGHLIGHT,
        'config/', $environment, '/AllConfig.php' . CLI_ERROR . '.', CLI_BASE, PHP_EOL;
        throw new OtraException(code: 1, exit: true);
      }
    } else 
    {
      $prop = $refClass->getProperty($name);
      $prop->setAccessible(true);
      $config[$name] = $prop->getValue();
    }
  }

  return $config;
}
