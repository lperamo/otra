<?php
declare(strict_types=1);
require CORE_PATH . 'console/Tools.php';

/**
 * @param string $bundleBasePath The path where we put modules
 * @param string $moduleName
 */
function createModule(string &$bundleBasePath, string &$moduleName) : void
{
  mkdir($bundleBasePath . $moduleName, 0755);
  mkdir($bundleBasePath . $moduleName . '/controllers', 0755);
  mkdir($bundleBasePath . $moduleName . '/views', 0755);
  echo CLI_GREEN, 'Basic folder architecture created for ', CLI_LIGHT_CYAN, $moduleName, CLI_GREEN, PHP_EOL;
}

$bundleBasePath = BASE_PATH . 'bundles/' . $argv[2] . '/';

$module = promptUser('What is the name of the first module ?(y or n)');

while($module !== 'n')
{
  createModule($bundleBasePath, $module);
  $module = promptUser('Do you want another module ?(y or n)');
}
?>