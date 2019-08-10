<?php
if (function_exists('createModule') === false)
{
  /**
   * @param string $bundleBasePath The path where we put modules
   * @param string $moduleName
   * @param bool   $interactive
   */
  function createModule(string $bundleBasePath, string &$moduleName, bool &$interactive): void
  {
    $modulePath = $bundleBasePath . $moduleName;
    $moduleAlreadyExistsSentence = CLI_RED . 'The module ' . CLI_LIGHT_CYAN . $moduleName . CLI_RED . ' already exists.';

    // If the folder does not exist and we are not in interactive mode, we exit the program.
    while (file_exists($modulePath) === true)
    {
      if ($interactive === false)
      {
        echo $moduleAlreadyExistsSentence, PHP_EOL;
        exit(1);
      }

      $moduleName = promptUser($moduleAlreadyExistsSentence . ' Try another folder name (type n to stop):');

      if ($moduleName === 'n')
        exit(0);

      $modulePath = $bundleBasePath . $moduleName;

      // We clean the screen
      echo DOUBLE_ERASE_SEQUENCE;
    }

    mkdir($modulePath, 0755);
    mkdir($modulePath . '/controllers', 0755);
    mkdir($modulePath . '/views', 0755);
    echo CLI_GREEN, 'Basic folder architecture created for ', CLI_LIGHT_CYAN, substr($modulePath,
      strlen(BASE_PATH)), CLI_GREEN, '.', END_COLOR, PHP_EOL;
  }

  /**
   * @param bool   $interactive
   * @param string $bundleName
   * @param string $moduleName
   */
  function moduleHandling(bool $interactive, string $bundleName, string $moduleName)
  {
    // This constant is already defined if we have created a bundle on the process via CheckModuleExistence.php
    if (defined('BUNDLE_BASE_PATH') === false)
      define('BUNDLE_BASE_PATH', BASE_PATH . 'bundles/' . $bundleName . '/');

    if ($interactive === true)
    {
      while ($moduleName !== 'n')
      {
        createModule(BUNDLE_BASE_PATH, $moduleName, $interactive);
        $moduleName = promptUser('What is the name of the next module ? (type n to stop)');
      }
    } else
      createModule(BUNDLE_BASE_PATH, $moduleName, $interactive);
  }
}
?>