<?php
declare(strict_types=1);
namespace otra\src\tools;

use const otra\cache\php\DIR_SEPARATOR;

/**
 * Returns true if the PHP file exists, false otherwise.
 *
 * @param string $basePath
 * @param string $route
 *
 * @return bool
 */
function checkPHPPath(string $basePath, string $route) : bool
{
  $routePath = $basePath . 'php/';

  if (str_starts_with($route, 'otra_'))
    $routePath .= 'otraRoutes/';

  return file_exists($routePath . $route . '.php');
}

/**
 * Returns true if the resource file exists, false otherwise.
 *
 * @param string $resourceExtension
 * @param string $basePath
 * @param string $shaName
 *
 * @return bool
 */
function checkResourcePath(
  string $resourceExtension,
  string $basePath,
  string $shaName,
) : bool
{
  return file_exists($basePath . $resourceExtension . DIR_SEPARATOR . $shaName. '.gz');
}
