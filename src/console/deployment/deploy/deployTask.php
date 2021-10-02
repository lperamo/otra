<?php
/**
 * Production deployment task
 *
 * @author Lionel Péramo
 * @package otra\console\deployment
 */
declare(strict_types=1);
namespace otra\console\deployment\deploy;

use otra\config\AllConfig;
use DirectoryIterator;
use otra\OtraException;
use otra\tools\workers\{Worker, WorkerManager};
use const otra\cache\php\{BASE_PATH,CONSOLE_PATH,CORE_PATH};
use const otra\console\{CLI_ERROR, CLI_INFO, CLI_SUCCESS, END_COLOR, SUCCESS};
use function otra\tools\cliCommand;

const DEPLOY_ARG_MASK = 2,
  DEPLOY_ARG_VERBOSE = 3,
  DEPLOY_ARG_GCC_LEVEL_COMPILATION = 4,

  GEN_BOOTSTRAP_ARG_CLASS_MAPPING = 2,
  GEN_BOOTSTRAP_ARG_VERBOSE = 3,

  BUILD_DEV_MASK_SCSS = 1,
  BUILD_DEV_MASK_TS = 2,

  DEPLOY_MASK_PHP_BEFORE_RSYNC = 1,
  DEPLOY_MASK_JS_BEFORE_RSYNC = 2,
  DEPLOY_MASK_CSS_BEFORE_RSYNC = 4,
  DEPLOY_MASK_TEMPLATES_MANIFEST_AND_SVG_BEFORE_RSYNC = 8,

  GEN_ASSETS_MASK_TEMPLATE = 1,
  GEN_ASSETS_MASK_SVG = 16,

  OTRA_CLI_CONTROL_MODE = "\033[",

  OTRA_CLI_COMMAND_SSH_AND_PORT = 'ssh -p ',
  OTRA_CLI_COMMAND_RECURSIVE_MKDIR = ' mkdir -p ',
  TIMEOUT = 60,
  PRELOAD_FILENAME = 'preload.php';

// **** Checking the deployment config parameters ****
if (!isset(AllConfig::$deployment))
{
  echo CLI_ERROR . 'You have not defined deployment configuration.', END_COLOR, PHP_EOL;
  throw new OtraException(code: 1, exit: true);
}

$deploymentParameters = ['server', 'port', 'folder', 'privateSshKey', 'gcc'];

foreach($deploymentParameters as $deploymentParameter)
{
  if (!isset(AllConfig::$deployment[$deploymentParameter]))
  {
    echo CLI_ERROR . 'You have not defined the ' . $deploymentParameter . ' in deployment configuration.', END_COLOR, PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }
}

unset($deploymentParameter);

$mainBundlesFolder = BASE_PATH . 'bundles';

if (!file_exists($mainBundlesFolder))
{
  echo CLI_ERROR . 'You do not have any bundles yet to deploy!', END_COLOR, PHP_EOL;
  throw new OtraException(code: 1, exit: true);
}

$deployMask = (isset($argv[DEPLOY_ARG_MASK])) ? (int) $argv[DEPLOY_ARG_MASK] : 0;
define(__NAMESPACE__ . '\\VERBOSE', (isset($argv[DEPLOY_ARG_VERBOSE])) ? (int) $argv[DEPLOY_ARG_VERBOSE] : 0);
define(
  __NAMESPACE__ . '\\DEPLOY_GCC_LEVEL_COMPILATION',
  isset($argv[DEPLOY_ARG_GCC_LEVEL_COMPILATION]) ? (int) $argv[DEPLOY_ARG_GCC_LEVEL_COMPILATION] : 1
);

if ($deployMask & DEPLOY_MASK_PHP_BEFORE_RSYNC)
{
  // We generate the class mapping...
  require CONSOLE_PATH . 'deployment/genClassMap/genClassMapTask.php';

  echo 'Launching routes update...', PHP_EOL;
  require CONSOLE_PATH . 'deployment/updateConf/updateConfTask.php';

  // bootstraps
  $argv[GEN_BOOTSTRAP_ARG_CLASS_MAPPING] = 0; // prevents the class mapping
  $argv[GEN_BOOTSTRAP_ARG_VERBOSE] = VERBOSE; // if true, print warnings when the task fails
  require CONSOLE_PATH . 'deployment/genBootstrap/genBootstrapTask.php';
}

require CORE_PATH . 'tools/cli.php';

$buildDevMode = 0;

if (($deployMask & DEPLOY_MASK_JS_BEFORE_RSYNC) >> 1)
  $buildDevMode |= BUILD_DEV_MASK_TS;

if (($deployMask & DEPLOY_MASK_CSS_BEFORE_RSYNC) >> 2)
  $buildDevMode |= BUILD_DEV_MASK_SCSS;

if ($buildDevMode > 0)
{
  echo END_COLOR, 'Assets transcompilation...';

  // Generates all TypeScript (and CSS files ?) that belong to the project files, verbosity and gcc parameters took into account
  [, $output] = cliCommand(
    'php bin/otra.php buildDev ' . VERBOSE . ' ' . $buildDevMode . ' ' . ((string)AllConfig::$deployment['gcc']),
    CLI_ERROR . 'There was a problem during the assets transcompilation.' . END_COLOR . PHP_EOL
  );

  echo OTRA_CLI_CONTROL_MODE . 3 . "D", SUCCESS, $output, PHP_EOL;
}

$genAssetsMode = 0;

if (($deployMask & DEPLOY_MASK_JS_BEFORE_RSYNC) >> 1)
  $genAssetsMode |= DEPLOY_MASK_JS_BEFORE_RSYNC;

if (($deployMask & DEPLOY_MASK_CSS_BEFORE_RSYNC) >> 2)
  $genAssetsMode |= DEPLOY_MASK_CSS_BEFORE_RSYNC;

if (($deployMask & DEPLOY_MASK_TEMPLATES_MANIFEST_AND_SVG_BEFORE_RSYNC) >> 3)
  $genAssetsMode |= DEPLOY_MASK_TEMPLATES_MANIFEST_AND_SVG_BEFORE_RSYNC | GEN_ASSETS_MASK_TEMPLATE | GEN_ASSETS_MASK_SVG;

if ($genAssetsMode > 0)
{
  echo 'Assets minification and compression...';
  // Generates all TypeScript (and CSS files ?) that belong to the project files, verbosity and gcc parameters took into account
  [, $output] = cliCommand(
    'php bin/otra.php genAssets ' . $genAssetsMode . ' ' . DEPLOY_GCC_LEVEL_COMPILATION,
    CLI_ERROR . 'There was a problem during the assets minification and compression.'
  );

  echo OTRA_CLI_CONTROL_MODE . 3 . "D", SUCCESS, $output, PHP_EOL;
}

// Deploy the files on the server...
[
  'server' => $server,
  'port' => $destinationPort,
  'folder' => $folder,
  'privateSshKey' => $privateSshKey
] = AllConfig::$deployment;

echo PHP_EOL, 'Deploys the files on the server ', CLI_INFO, $server, ':', $destinationPort, END_COLOR, ' in ',
CLI_INFO, $folder . ' ...', END_COLOR, PHP_EOL;

/* --delete allows to delete things that are no present anymore on the source to keep a really synchronized folder
 * -P It combines the flags –progress and –partial.
 * The first of these gives you a progress bar for the transfers.
 * The second allows you to resume interrupted transfers.
 * -u => 'update' to not send files that are older than those in the server
 * -r is for recursive
 * -R is for --relative, it will create missing folders
 * -m remove empty folders */

define(
  __NAMESPACE__ . '\\START_COMMAND',
  'rsync -qzaruvhP --delete -e \'ssh -i '  . $privateSshKey . ' -p ' . $destinationPort
);
define(
  __NAMESPACE__ . '\\START_COMMAND_RELATIVE_RSYNC',
  'rsync -qzaruhPR --delete -e \'ssh -i ' . $privateSshKey . ' -p ' . $destinationPort
);
define(__NAMESPACE__ . '\\STRLEN_BASEPATH', strlen(BASE_PATH));

/**
 * See which files to send and which files to keep
 *
 * @param string $folderToAnalyze
 *
 * @return Worker[]
 */
$seekingToSendFiles = function (string $folderToAnalyze)
use (&$seekingToSendFiles, &$folder, &$destinationPort, &$server)
: array
{
  $bundleFolders = new DirectoryIterator($folderToAnalyze);
  $newWorkersToChain = [];

  foreach ($bundleFolders as $bundleFolder)
  {
    if (
      $bundleFolder->isDot()
      || $bundleFolder->isFile()
      || in_array($bundleFolder->getFilename(), ['config',  'resources', 'controllers', 'services', 'tasks'])
    )
      continue;

    $folderRealPath = $bundleFolder->getRealPath();
    $folderRelativePath = substr($folderRealPath, STRLEN_BASEPATH);

    $newWorkersToChain = [
      ...$newWorkersToChain,
      new Worker(
        START_COMMAND .
        '\' -m  ' . $folderRealPath . '/ ' . $server . ':' . $folder . $folderRelativePath,
        $folderRelativePath . ' folder' . CLI_SUCCESS . ' ✔' . END_COLOR,
        'Sending ' . $folderRelativePath . ' folder ...',
        null,
        VERBOSE
      ),
      ...$seekingToSendFiles($folderRealPath)
    ];
  }

  return $newWorkersToChain;
};

$workerManager = new WorkerManager();
$workerManager->attach(
  new Worker(
    OTRA_CLI_COMMAND_SSH_AND_PORT . $destinationPort . ' ' . $server . OTRA_CLI_COMMAND_RECURSIVE_MKDIR . $folder,
    'Site main folder' . CLI_SUCCESS . ' ✔' . END_COLOR,
    'Creating the site main folder if needed ...',
    'The site main folder cannot be created. An error occurred.',
    VERBOSE,
    TIMEOUT,
    [
      // The main folder is created, we can do the rest
      new Worker(
        START_COMMAND_RELATIVE_RSYNC . '\' cache ' . $server . ':' . $folder,
        'Cache' . CLI_SUCCESS . ' ✔' . END_COLOR,
        'Sending cache ...',
        null,
        VERBOSE
      ),
      // if there is a preload configuration file, we send it
      ...(
        file_exists(BASE_PATH . PRELOAD_FILENAME)
        ? [
          new Worker(
            START_COMMAND . '\' ' .  PRELOAD_FILENAME . ' ' . $server . ':' . $folder . PRELOAD_FILENAME,
            'Preload file' . CLI_SUCCESS . ' ✔' . END_COLOR,
            'Sending preload file ...',
            null,
            VERBOSE
          )
        ]
        : []
      ),
      new Worker(
        START_COMMAND . '\' web/ ' . $server . ':' . $folder . 'web/',
        'Web folder' . CLI_SUCCESS . ' ✔' . END_COLOR,
        'Sending web folder ...',
        null,
        VERBOSE
      ),
      new Worker(
        OTRA_CLI_COMMAND_SSH_AND_PORT . $destinationPort . ' ' . $server . OTRA_CLI_COMMAND_RECURSIVE_MKDIR .
        $folder . 'config',
        'Config folder' . CLI_SUCCESS . ' ✔' . END_COLOR,
        'Creating the config folder ...',
        null,
        VERBOSE,
        TIMEOUT,
        [
          new Worker(
            START_COMMAND . '\' config/prodConstants.php ' . $server . ':' . $folder . 'config/constants.php',
            'OTRA constants' . CLI_SUCCESS . ' ✔' . END_COLOR,
            'Adding the OTRA constants ...',
            null,
            VERBOSE
          )
        ]
      ),
      new Worker(
        OTRA_CLI_COMMAND_SSH_AND_PORT . $destinationPort . ' ' . $server . OTRA_CLI_COMMAND_RECURSIVE_MKDIR .
        $folder . 'vendor',
        'Vendor folder' . CLI_SUCCESS . ' ✔' . END_COLOR,
        'Creating the vendor folder ...',
        null,
        VERBOSE,
        TIMEOUT,
        [
          new Worker(
            START_COMMAND .
            '\' --delete-excluded -m --include=\'otra/otra/src/entryPoint.php\' --include=\'otra/otra/src/tools/translate.php\'' .
            ' --include=\'otra/otra/src/templating/blocks.php\' --include=\'otra/otra/src/prod/ProdControllerTrait.php\'' .
            ' --include=\'otra/otra/src/services/securityService.php\'' .
            ' --include=\'otra/otra/src/views/layout.phtml\'' .
            ' --include=\'otra/otra/src/views/errors/error404.phtml\'' .
            ' --include=\'*/\' --exclude=\'*\' vendor/ ' . $server . ':' . $folder .
            'vendor/',
            'OTRA templating engine, the translate tool, the production controller and the 404 errors pages' .
            CLI_SUCCESS . ' ✔' . END_COLOR,
            'Sending the OTRA templating engine, the translate tool and the production controller ...'
          )
        ]
      ),
      new Worker(
        OTRA_CLI_COMMAND_SSH_AND_PORT . $destinationPort . ' ' . $server . OTRA_CLI_COMMAND_RECURSIVE_MKDIR .
        $folder . 'bundles',
        'Bundles folder' . CLI_SUCCESS . ' ✔' . END_COLOR,
        'Creating the bundles folder ...',
        null,
        VERBOSE
      )
      ,
      ...$seekingToSendFiles($mainBundlesFolder),
      new Worker(
        START_COMMAND_RELATIVE_RSYNC . '\' --include=\'*/\' --exclude=\'*\' logs ' . $server . ':' . $folder,
        'Log folder' . CLI_SUCCESS . ' ✔' . END_COLOR,
        'Checking log folder ...',
        null,
        VERBOSE
      )
    ]
  )
);

// Launching the workers
while (0 < count($workerManager::$workers))
  $workerManager->listen();

unset($workerManager);
