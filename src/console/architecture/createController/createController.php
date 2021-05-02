<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

use otra\OtraException;
use function otra\console\{architecture\createFolder, promptUser};
use const otra\console\{CLI_BASE, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, END_COLOR};

require CONSOLE_PATH . 'architecture/createFolder.php';

/**
 * Create the folders that contains all the controllers folders.
 *
 * @param string $controllersFolder
 */
function createControllersFolder(string $controllersFolder) : void
{
  if (!file_exists($controllersFolder))
  {
    mkdir($controllersFolder, 0755);
    echo CLI_BASE, 'Folder ', CLI_INFO_HIGHLIGHT, substr($controllersFolder, 0, -1), CLI_BASE,
      ' created', CLI_SUCCESS, ' ✔', END_COLOR, PHP_EOL;
  }
}

/**
 * Creates the folder of the specified controller.
 *
 * @param string $controllersFolder
 * @param string $controllerName
 * @param bool   $interactive
 *
 * @throws OtraException
 */
function createController(string $controllersFolder, string $controllerName, bool $interactive) : void
{
  $controllerPath = $controllersFolder . $controllerName;

  // If the folder does not exist and we are not in interactive mode, we exit the program.
  createFolder($controllerPath, $controllersFolder, 'controller', $interactive);
  echo CLI_BASE, 'Folder ', CLI_INFO_HIGHLIGHT, substr($controllerPath, strlen(BASE_PATH)), CLI_BASE,
    ' created', CLI_SUCCESS, ' ✔', END_COLOR, PHP_EOL;
}

/**
 * @param bool   $interactive
 * @param string $controllersFolder
 * @param string $controllerName
 *
 * @throws OtraException
 */
function controllerHandling(bool $interactive, string $controllersFolder, string &$controllerName) : void
{
  createControllersFolder($controllersFolder);

  if ($interactive)
  {
    while($controllerName !== 'n')
    {
      createController($controllersFolder, $controllerName, $interactive);
      $controllerName = promptUser('What is the name of the next controller ? (type n to stop)');
    }
  } else
    createController($controllersFolder, $controllerName, $interactive);
}

