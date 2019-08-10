<?php

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

  $controllerAlreadyExistsSentence = CLI_RED . 'The controller ' . CLI_LIGHT_CYAN . $controllerName . CLI_RED . ' already exists.';

  // If the folder does not exist and we are not in interactive mode, we exit the program.
  while (file_exists($controllerPath) === true)
  {
    if ($interactive === false)
    {
      echo $controllerAlreadyExistsSentence, PHP_EOL;
      exit(1);
    }

    $controllerName = promptUser($controllerAlreadyExistsSentence . ' Try another folder name (type n to stop):');

    if ($controllerName === 'n')
      exit(0);

    $controllerPath = $controllersFolder . $controllerName;

    // We clean the screen
    echo DOUBLE_ERASE_SEQUENCE;
  }

  mkdir($controllerPath, 0755);
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