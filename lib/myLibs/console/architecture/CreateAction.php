<?php

/**
 * Creates the folder of the specified controller.
 *
 * @param string $bundleName
 * @param string $moduleName
 * @param string $controllerName
 * @param string $controllerPath Absolute path to the controller
 * @param string $actionName
 * @param bool   $interactive
 */
function createAction(string $bundleName, string $moduleName,
                      string $controllerName, string &$controllerPath, string $actionName, bool $interactive)
{
  $upperActionName = ucfirst($actionName);
  $actionPath = $controllerPath . $upperActionName . 'Action.php';

  $actionAlreadyExistsSentence = CLI_RED . 'The action ' . CLI_LIGHT_CYAN . substr($actionPath, strlen(BASE_PATH)) . CLI_RED . ' already exists.';

  while (file_exists($actionPath) === true)
  {
    // If the file does not exist and we are not in interactive mode, we exit the program.
    if ($interactive === false)
    {
      echo $actionAlreadyExistsSentence, PHP_EOL;
      exit(1);
    }

    $actionName = promptUser($actionAlreadyExistsSentence . ' Try another file name (type n to stop):');

    if ($actionName === 'n')
      exit(0);

    $upperActionName = ucfirst($actionName);
    $actionPath = $controllerPath . $upperActionName . 'Action.php';

    // We clean the screen
    echo DOUBLE_ERASE_SEQUENCE;
  }

  file_put_contents(
    $actionPath,
    '<?php
namespace bundles\\' . $bundleName . '\\' . $moduleName . '\\controllers\\' . $controllerName . ';

use lib\myLibs\Controller;

class ' . $upperActionName . 'Action extends Controller
{
  public function ' . $actionName . 'Action() {
    
  }
}
?>
');

  echo CLI_LIGHT_GREEN, 'Action ', CLI_LIGHT_CYAN, substr($actionPath,
    strlen(BASE_PATH)), CLI_LIGHT_GREEN, ' created.', END_COLOR, PHP_EOL;

  $viewFolder = BASE_PATH . 'bundles/' . $bundleName . '/' . $moduleName . '/views/' . $controllerName;

  // If the action folder does not exist
  if (file_exists($viewFolder) === false)
    mkdir($viewFolder);
  else
    echo CLI_YELLOW, 'For your information, the folder ', CLI_LIGHT_CYAN, $viewFolder, CLI_YELLOW, ' already existed.',
      END_COLOR, PHP_EOL;

  $template = $viewFolder . '/' . $actionName . '.phtml';

  // If the template file already exists
  if (file_exists($template) === true)
    echo CLI_YELLOW, 'For your information, the template file ', CLI_LIGHT_CYAN, $template, CLI_YELLOW,
      ' already existed.', END_COLOR, PHP_EOL;

  // We just create an empty template file
  touch($template);
}

/**
 * @param bool   $interactive
 * @param string $bundleName
 * @param string $moduleName
 * @param string $controllerName
 * @param string $controllerPath
 * @param string $actionName
 */
function actionHandling(bool $interactive, string $bundleName, string $moduleName, string $controllerName,
                        string &$controllerPath, string &$actionName)
{
  /** @var string $interactive */
  if ($interactive === true)
  {
    while($actionName !== 'n')
    {
      createAction($bundleName, $moduleName, $controllerName, $controllerPath, $actionName, $interactive);
      $actionName = promptUser('What is the name of the next action ? (type n to stop)');
    }
  } else
    createAction($bundleName, $moduleName, $controllerName, $controllerPath, $actionName, $interactive);
}
?>
