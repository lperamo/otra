<?php

require CORE_PATH . 'console/architecture/CreateFolder.php';

/**
 * Create the folders that contains all the controllers folders.
 *
 * @param string $controllersFolder
 */
function createControllersFolder(string $controllersFolder)
{
  if (file_exists($controllersFolder) === false)
  {
    mkdir($controllersFolder, 0755);
    echo CLI_LIGHT_GREEN, 'Folder ', CLI_LIGHT_CYAN, substr($controllersFolder, 0, -1), CLI_LIGHT_GREEN,
    ' created.', PHP_EOL;
  }
}

/**
 * Creates the folder of the specified controller.
 *
 * @param string $controllersFolder
 * @param string $controllerName
 * @param bool   $interactive
 */
function createController(string $controllersFolder, string $controllerName, bool $interactive)
{
  $controllerPath = $controllersFolder . $controllerName;

  // If the folder does not exist and we are not in interactive mode, we exit the program.
  createFolder($controllerPath, $controllersFolder, $controllerName, 'controller', $interactive);

  echo CLI_LIGHT_GREEN, 'Folder ', CLI_LIGHT_CYAN, substr($controllersFolder, strlen(BASE_PATH),
    -1), CLI_LIGHT_GREEN, ' created.', END_COLOR, PHP_EOL;
}

/**
 * @param bool   $interactive
 * @param string $controllersFolder
 * @param string $controllerName
 */
function controllerHandling(bool $interactive, string &$controllersFolder, string &$controllerName)
{
  /** @var string $interactive */
  if ($interactive === true)
  {
    createControllersFolder($controllersFolder);

    while($controllerName !== 'n')
    {
      createController($controllersFolder, $controllerName, $interactive);
      $controllerName = promptUser('What is the name of the next controller ? (type n to stop)');
    }
  } else
  {
    createControllersFolder($controllersFolder);;
    createController($controllersFolder, $controllerName, $interactive);
  }
}
?>