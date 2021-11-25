<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture\constants
{
  const
    ARG_BUNDLE_NAME = 2,
    ARG_CONTROLLER_NAME = 4,
    ARG_FORCE = 7,
    ARG_MODULE_NAME = 3,
    ARG_INTERACTIVE = 6;
}

namespace otra\console\architecture\createHelloWorld
{
  use otra\OtraException;
  use function otra\console\deployment\updateConf\updateConf;
  use const otra\cache\php\{BASE_PATH, BUNDLES_PATH, CONSOLE_PATH, CORE_PATH, DIR_SEPARATOR};
  use const otra\console\
  {
    CLI_BASE,
    CLI_ERROR,
    CLI_INFO,
    CLI_INFO_HIGHLIGHT,
    CLI_TABLE,
    CLI_WARNING,
    ERASE_SEQUENCE,
    END_COLOR,
    SUCCESS};
  use const otra\console\architecture\constants\
  {ARG_BUNDLE_NAME, ARG_CONTROLLER_NAME, ARG_FORCE, ARG_MODULE_NAME, ARG_INTERACTIVE};
  use function otra\console\architecture\{actionHandling, checkBooleanArgument};
  use function otra\tools\{cliCommand,copyFileAndFolders};

  if (file_exists(BUNDLES_PATH . 'HelloWorld'))
  {
    echo CLI_WARNING, 'The bundle ', CLI_INFO, 'HelloWorld', CLI_WARNING, ' already exists.', END_COLOR, PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }

  const ARG_ACTION_NAME = 5;

  $argv = [
    '0' => $argv[0],
    '1' => $argv[1],
    ARG_BUNDLE_NAME => 'HelloWorld',
    ARG_MODULE_NAME => 'frontend',
    ARG_CONTROLLER_NAME => 'index',
    ARG_ACTION_NAME => 'home',
    ARG_INTERACTIVE => 'false',
    ARG_FORCE => 'true'
  ];

  $bundleMask = 9;

  // load console tools in order to ask questions (the following includes need those functions)
  // beware, we are using require_once only for tests purposes
  require_once CONSOLE_PATH . 'tools.php';

  const ARCHITECTURE_FOLDER = CONSOLE_PATH . 'architecture/';
  define(__NAMESPACE__ . '\\ACTION_NAME', $argv[ARG_ACTION_NAME]);

  // It creates bundles, modules, controllers, actions and their related folders
  require ARCHITECTURE_FOLDER . 'checkBooleanArgument.php';
  $interactive = checkBooleanArgument($argv, ARG_INTERACTIVE, 'interactive');
  $consoleForce = checkBooleanArgument($argv, ARG_FORCE, 'force', 'false');
  /** @var string $bundleName */
  $bundleMask = 9;
  require ARCHITECTURE_FOLDER . 'createBundle/checkBundleExistence.php';
  /** @var string $moduleName */
  require ARCHITECTURE_FOLDER . 'createModule/checkModuleExistence.php';
  /** @var string $controllerName */
  /** @var string $controllerPath */
  require ARCHITECTURE_FOLDER . 'createController/checkControllerExistence.php';
  require ARCHITECTURE_FOLDER . 'createAction/createAction.php';
  require CORE_PATH . 'tools/copyFilesAndFolders.php';

  actionHandling(
    $interactive,
    $bundleName,
    $moduleName,
    $controllerName,
    $controllerPath,
    ACTION_NAME,
    $consoleForce
  );

  define(__NAMESPACE__ . '\\BASE_PATH_LENGTH', strlen(BASE_PATH));

  /**
   * @param string $source
   * @param string $destination
   * @param string $message
   *
   * @throws OtraException
   */
  function copyAndShow(string $source, string $destination, string $message) : void
  {
    copyFileAndFolders([HELLO_WORLD_STARTER_FOLDER . $source], [$destination]);
    echo $message, CLI_TABLE,  'BASE_PATH + ', CLI_INFO_HIGHLIGHT, substr($destination, BASE_PATH_LENGTH),
    END_COLOR, ' created', SUCCESS;
  }

  // We fixes the action file generated by filling the method (but here we just replaced the whole file)
  define(__NAMESPACE__ . '\\BUNDLE_FOLDER', BUNDLES_PATH . $bundleName . DIR_SEPARATOR);
  define(__NAMESPACE__ . '\\MODULE_FOLDER', BUNDLE_FOLDER . $moduleName . DIR_SEPARATOR);
  $starterAction = MODULE_FOLDER . 'controllers/' . $argv[ARG_CONTROLLER_NAME] . DIR_SEPARATOR .
    ucfirst(ACTION_NAME) . 'Action.php';
  const HELLO_WORLD_STARTER_FOLDER = ARCHITECTURE_FOLDER . 'starters/helloWorld/';
  copyFileAndFolders([HELLO_WORLD_STARTER_FOLDER . 'HomeAction.php'], [$starterAction]);
  echo 'Action filled', SUCCESS;

  // Adds a routes configuration file
  copyAndShow(
    'Routes.php',
    BUNDLE_FOLDER . 'config/Routes.php',
    'Route configuration file '
  );

  // Adds a security configuration file
  copyAndShow(
    'security',
    BUNDLE_FOLDER . 'config/security/',
    'Security configuration folder '
  );

  // We create a starter layout
  copyAndShow(
    'layout.phtml',
    BUNDLE_FOLDER . 'views/' . 'layout.phtml',
    'Starter layout '
  );

  // We create the action template that will use this layout
  copyAndShow(
    'home.phtml',
    MODULE_FOLDER . 'views/' . $controllerName . DIR_SEPARATOR . ACTION_NAME . '.phtml',
    'Starter template '
  );

  // We add the stylesheets
  echo 'Adding stylesheets...', PHP_EOL;
  const HELLO_WORLD_SCSS_FOLDER = MODULE_FOLDER . 'resources/scss/pages/HelloWorld/',
    HELLO_WORLD_STARTER_SCSS_FOLDER = HELLO_WORLD_STARTER_FOLDER . 'scss/',
    HELLO_WORLD_STARTER_SCSS_COMMON = '_common.scss',
    HELLO_WORLD_STARTER_SCSS_PRINT = 'print.scss',
    HELLO_WORLD_STARTER_SCSS_SCREEN = 'screen.scss';
  copyFileAndFolders(
    [
      HELLO_WORLD_STARTER_SCSS_FOLDER . HELLO_WORLD_STARTER_SCSS_COMMON,
      HELLO_WORLD_STARTER_SCSS_FOLDER . HELLO_WORLD_STARTER_SCSS_PRINT,
      HELLO_WORLD_STARTER_SCSS_FOLDER . HELLO_WORLD_STARTER_SCSS_SCREEN
    ],
    [
      HELLO_WORLD_SCSS_FOLDER . HELLO_WORLD_STARTER_SCSS_COMMON,
      HELLO_WORLD_SCSS_FOLDER . HELLO_WORLD_STARTER_SCSS_PRINT,
      HELLO_WORLD_SCSS_FOLDER . HELLO_WORLD_STARTER_SCSS_SCREEN
    ]
  );

  echo ERASE_SEQUENCE, 'Stylesheets added', SUCCESS;

  echo 'Adding favicons...', PHP_EOL;
  const HELLO_WORLD_IMAGES_PATH = HELLO_WORLD_STARTER_FOLDER . 'img/';

  copyFileAndFolders(
    [
      HELLO_WORLD_IMAGES_PATH . 'favicon.ico',
      HELLO_WORLD_IMAGES_PATH . 'favicon.png'
    ],
    [
      BASE_PATH . 'web/favicon.ico',
      BASE_PATH . 'web/favicon.png'
    ]
  );
  echo ERASE_SEQUENCE, 'Favicons added', SUCCESS;

  // We update the routes' configuration as we just add one route.
  require CONSOLE_PATH . 'deployment/updateConf/updateConfTask.php';
  updateConf('7', (string) $argv[ARG_BUNDLE_NAME]);

  echo CLI_BASE, 'Building the CSS assets...', END_COLOR, PHP_EOL;
  require CORE_PATH . 'tools/cli.php';
  [,$output] = cliCommand(
    'php ' . BASE_PATH . 'bin/otra.php buildDev 0 1',
    CLI_ERROR . 'There was a problem during the assets transcompilation.' . END_COLOR . PHP_EOL
  );

  echo $output, PHP_EOL, CLI_BASE, 'CSS assets built', SUCCESS;

  /* We update the class mapping since we have one action more.
   * Maybe we have launched this task with no class map and then the VERBOSE constant has already been defined during the
   * class map generation task.
  */
  if (!defined(__NAMESPACE__ . '\\VERBOSE'))
    define(__NAMESPACE__ . '\\VERBOSE', 0);

  require CONSOLE_PATH . 'deployment/genClassMap/genClassMapTask.php';

  echo 'You can launch this example via the url ', CLI_INFO_HIGHLIGHT, '/helloworld', END_COLOR,
    '.', PHP_EOL, 'You can launch a PHP internal web server by typing ', CLI_INFO_HIGHLIGHT, 'otra serve', END_COLOR, '.',
    PHP_EOL;
}
