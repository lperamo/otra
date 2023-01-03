<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture;

use otra\OtraException;
use const otra\cache\php\{BASE_PATH, BUNDLES_PATH, CONSOLE_PATH, DIR_SEPARATOR, SPACE_INDENT};
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, CLI_WARNING, END_COLOR};
use const otra\console\constants\DOUBLE_ERASE_SEQUENCE;
use function otra\console\deployment\genClassMap\genClassMap;
use function otra\console\deployment\updateConf\updateConf;
use function otra\console\promptUser;

const
  SPACE_INDENT_2 = SPACE_INDENT . SPACE_INDENT,
  SPACE_INDENT_3 = SPACE_INDENT_2 . SPACE_INDENT;

/**
 * Creates the folder of the specified controller.
 *
 * @param string $controllerPath Absolute path to the controller
 * @param bool   $interactive    Do we allow questions to the user?
 * @param bool   $consoleForce   Determines whether we show an error when something is missing in non-interactive mode
 *                               or not. The false value by default will stop the execution if something does not exist
 *                               and shows an error.
 *
 * @throws OtraException
 */
function createActionCore(string $bundleName, string $moduleName, string $controllerName,
                      string $controllerPath, string $actionName, bool $interactive, bool $consoleForce) : void
{
  $upperActionName = ucfirst($actionName);
  $actionPath = $controllerPath . $upperActionName . 'Action.php';

  $actionAlreadyExistsSentence = CLI_ERROR . 'The action ' . CLI_INFO_HIGHLIGHT .
    substr($actionPath, strlen(BASE_PATH)) . CLI_ERROR . ' already exists.' . END_COLOR;

  while (file_exists($actionPath))
  {
    // If the file does not exist, and we are not in interactive mode, we exit the program.
    if (!$interactive)
    {
      echo $actionAlreadyExistsSentence, PHP_EOL;
      throw new OtraException(code: 1, exit: true);
    }

    $actionName = promptUser($actionAlreadyExistsSentence . ' Try another file name (type n to stop):');

    if ($actionName === 'n')
      throw new OtraException(exit: true);

    $upperActionName = ucfirst($actionName);
    $actionPath = $controllerPath . $upperActionName . 'Action.php';

    // We clean the screen
    echo DOUBLE_ERASE_SEQUENCE;
  }

  if (!defined(__NAMESPACE__ . '\\OTRA_ACTION_PATH'))
    define(
      __NAMESPACE__ . '\\OTRA_ACTION_PATH',
      'bundles\\' . $bundleName . '\\' . $moduleName . '\\controllers\\' . $controllerName
    );
  file_put_contents(
    $actionPath,
    '<?php
declare(strict_types=1);

namespace ' . OTRA_ACTION_PATH . ';

use otra\Controller;

/**
 * @package ' . OTRA_ACTION_PATH . '
 */
class ' . $upperActionName . 'Action extends Controller
{
  /**
   * ' . $upperActionName . 'Action constructor.
   *
   * @param array $otraParams [pattern, bundle, module, controller, action, route, js, css, internalRedirect]
   * @param array $params     [...getParams, ...postParams, etc.]
   */
  public function __construct(array $otraParams = [], array $params = [])
  {
    parent::__construct($otraParams, $params);
  }
}' . PHP_EOL);

  echo CLI_BASE, 'Action ', CLI_INFO_HIGHLIGHT, substr($actionPath,
    strlen(BASE_PATH)), CLI_BASE, ' created', CLI_SUCCESS, ' ✔', END_COLOR, PHP_EOL;

  $viewFolder = BUNDLES_PATH . $bundleName . DIR_SEPARATOR . $moduleName . '/views/' . $controllerName;

  // If the action folder does not exist
  if (!file_exists($viewFolder))
    mkdir($viewFolder, 0777, true);
  else
    echo CLI_WARNING, 'For your information, the folder ', CLI_INFO_HIGHLIGHT, $viewFolder, CLI_WARNING, ' already existed.',
      END_COLOR, PHP_EOL;

  $template = $viewFolder . DIR_SEPARATOR . $actionName . '.phtml';

  // If the template file already exists
  if (file_exists($template))
    echo CLI_WARNING, 'For your information, the template file ', CLI_INFO_HIGHLIGHT, $template, CLI_WARNING,
      ' already existed.', END_COLOR, PHP_EOL;

  // We just create an empty template file
  touch($template);

  if ($consoleForce)
    return;

  $routesConfigFolder = BUNDLES_PATH . $bundleName . '/config';
  $routeConfigurationFile = BUNDLES_PATH . $bundleName . '/config/Routes.php';
  $routeConfiguration = $controllerName . $upperActionName . "' => [" . PHP_EOL .
    SPACE_INDENT_2 . "'chunks' => ['/" . $controllerName . $upperActionName . "', '" . $bundleName . "', '"
    . $moduleName . "', '" . $controllerName . "', '" . $upperActionName . "Action']," . PHP_EOL .
    SPACE_INDENT_2 . "'resources' => [" . PHP_EOL .
    SPACE_INDENT_3 . "'template' => true" . PHP_EOL .
    SPACE_INDENT_2 . "]" . PHP_EOL .
    SPACE_INDENT . "]";

  if (!defined(__NAMESPACE__ . '\\PHP_FILE_START'))
     define(__NAMESPACE__ . '\\PHP_FILE_START', '<?php declare(strict_types=1);'. PHP_EOL);

  // If there already are actions for this bundle, we have to complete the configuration file not replace it
  if (file_exists($routesConfigFolder))
  {
    $routesArray = file_exists($routeConfigurationFile) ? require $routeConfigurationFile : [];
    $routesArray[$controllerName . $upperActionName] = [
      'chunks' => [
        DIR_SEPARATOR . $controllerName . $upperActionName,
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
    $routesArray = preg_replace('/\d+ => /', '', $routesArray);

    // now we can detect safely some other unwanted line breaks
    $routesArray = str_replace(',' . PHP_EOL . '      \'', ', \'',$routesArray);

    file_put_contents(
      $routeConfigurationFile,
      PHP_FILE_START .
      'return ' . $routesArray . ';' . PHP_EOL
    );
  } else
  { // If it's not the case, we replace it
    // First, we create the folder that will hold the routes' configuration file
    mkdir($routesConfigFolder);

    // Adds a routes' config file
    file_put_contents(
      $routeConfigurationFile,
      PHP_FILE_START .
      "return [" . PHP_EOL .
      SPACE_INDENT . "'" . $routeConfiguration . PHP_EOL .
      "];" . PHP_EOL
    );
  }

  echo 'Route configuration file ', CLI_INFO_HIGHLIGHT, $routeConfigurationFile, CLI_BASE, ' created', CLI_SUCCESS,
    ' ✔', PHP_EOL;

  // We update the routes' configuration as we just add one route.
  require_once CONSOLE_PATH . 'deployment/updateConf/updateConfTask.php';
  updateConf('2');

  // We update the class mapping since we have one action more.
  if (!defined(__NAMESPACE__ . '\\VERBOSE'))
    define(__NAMESPACE__ . '\\VERBOSE', 0);

  require_once CONSOLE_PATH . 'deployment/genClassMap/genClassMapTask.php';
  genClassMap([]);
}

/**
 * @param bool   $interactive    Do we allow questions to the user?
 * @param string $controllerPath Absolute path to the controller
 * @param bool   $consoleForce   Determines whether we show an error when something is missing in non-interactive mode
 *                               or not. The false value by default will stop the execution if something does not exist
 *                               and shows an error.
 *
 * @throws OtraException
 */
function actionHandling(bool $interactive, string $bundleName, string $moduleName, string $controllerName,
                        string $controllerPath, string $actionName, bool $consoleForce = false) : void
{
  if ($interactive)
  {
    while($actionName !== 'n')
    {
      createActionCore($bundleName, $moduleName, $controllerName, $controllerPath, $actionName, true, $consoleForce);
      $actionName = promptUser('What is the name of the next action ? (type n to stop)');
    }
  } else
    createActionCore($bundleName, $moduleName, $controllerName, $controllerPath, $actionName, false, $consoleForce);
}
