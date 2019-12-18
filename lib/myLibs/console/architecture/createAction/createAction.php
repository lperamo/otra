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
 * @param bool   $consoleForce
 */
function createAction(string $bundleName, string $moduleName, string $controllerName,
                      string &$controllerPath, string $actionName, bool $interactive, bool $consoleForce)
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

  if ($consoleForce === false)
  {
    $routesConfigFolder = BASE_PATH . 'bundles/' . $bundleName . '/config';
    $routeConfigurationFile = BASE_PATH . 'bundles/' . $bundleName . '/config/Routes.php';
    $routeConfiguration = $controllerName . $upperActionName . "' => [
        'chunks' => ['/" . $controllerName . $upperActionName . "', '" . $bundleName . "', '" . $moduleName
      . "', '" . $controllerName . "', '" . $upperActionName . "Action'],
        'resources' => [
          'template' => true
        ]
      ]";

    // If there already are actions for this bundle, we have to complete the configuration file not replace it
    if (file_exists($routesConfigFolder) === true)
    {
      $routesArray = file_exists($routeConfigurationFile) === true ? require $routeConfigurationFile : [];
      $routesArray[$controllerName . $upperActionName] = [
        'chunks' => [
          '/' . $controllerName . $upperActionName,
          $bundleName,
          $moduleName,
          $controllerName,
          $upperActionName . 'Action'
        ],
        'resources' => [
          'template' => true
        ]
      ];
      $routesArray = var_export($routesArray, true);

      // replaces by short array notation
      $routesArray = str_replace(
        [
          'array (',
          ')',
          '=> ' . PHP_EOL . '    [' . PHP_EOL . '      ',
          ',' . PHP_EOL . '    ]',
          '],' . PHP_EOL . '  ]'
        ],
        [
          '[',
          ']',
          '=> [ ',
          ']',
          ']' . PHP_EOL . '  ]'
        ],
        $routesArray
      );

      // removes useless numerical indexes
      $routesArray = preg_replace('/[0-9]{1,} => /', '', $routesArray);

      // now we can detect safely some other unwanted line breaks
      $routesArray = str_replace(',' . PHP_EOL . '      \'', ', \'',$routesArray);

      file_put_contents(
        $routeConfigurationFile,
        '<?php
  return ' . $routesArray . ';
?>'
      );
    } else
    { // If it's not the case, we replace it
      // First, we create the folder that will hold the routes configuration file
      mkdir($routesConfigFolder);

      // Adds a routes config file
      file_put_contents(
        $routeConfigurationFile,
        "<?php
      return [
        '" . $routeConfiguration . "
      ];
    ?>"
      );
    }

    echo 'Route configuration file ', CLI_LIGHT_CYAN, $routeConfigurationFile, CLI_GREEN, ' created.',
      PHP_EOL;

    // We update the routes configuration as we just add one route.
    require CORE_PATH . 'console/deployment/updateConf/updateConfTask.php';

    // We update the class mapping since we have one action more.
    if (defined('VERBOSE') === false)
      define('VERBOSE', 0);

    require CORE_PATH . 'console/deployment/genClassMap/genClassMapTask.php';
  }
}

/**
 * @param bool   $interactive
 * @param string $bundleName
 * @param string $moduleName
 * @param string $controllerName
 * @param string $controllerPath
 * @param string $actionName
 * @param bool   $consoleForce
 */
function actionHandling(bool $interactive, string $bundleName, string $moduleName, string $controllerName,
                        string &$controllerPath, string &$actionName, bool $consoleForce = false)
{
  /** @var string $interactive */
  if ($interactive === true)
  {
    while($actionName !== 'n')
    {
      createAction($bundleName, $moduleName, $controllerName, $controllerPath, $actionName, $interactive, $consoleForce);
      $actionName = promptUser('What is the name of the next action ? (type n to stop)');
    }
  } else
    createAction($bundleName, $moduleName, $controllerName, $controllerPath, $actionName, $interactive, $consoleForce);
}
?>
