<?php
/** Production deployment task
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace otra\console;

use config\AllConfig;
use \otra\OtraException;

define('DEPLOY_ARG_MASK', 2);
define('DEPLOY_ARG_VERBOSE', 3);
define('DEPLOY_ARG_GCC_LEVEL_COMPILATION', 4);

define('GEN_BOOTSTRAP_ARG_CLASS_MAPPING', 2);
define('GEN_BOOTSTRAP_ARG_VERBOSE', 3);

define('BUILD_DEV_MASK_SCSS', 1);
const BUILD_DEV_MASK_TS = 2;

define('DEPLOY_MASK_ONLY_RSYNC', 0);
define('DEPLOY_MASK_PHP_BEFORE_RSYNC', 1);
define('DEPLOY_MASK_JS_BEFORE_RSYNC', 2);
define('DEPLOY_MASK_CSS_BEFORE_RSYNC', 4);
define('DEPLOY_MASK_TEMPLATES_MANIFEST_AND_SVG_BEFORE_RSYNC', 8);

define('GEN_ASSETS_MASK_TEMPLATE', 1);
define('GEN_ASSETS_MASK_MANIFEST', 8);
define('GEN_ASSETS_MASK_SVG', 16);

define('OTRA_CLI_CONTROL_MODE', "\033[");

define('OTRA_CLI_COMMAND_SSH_AND_PORT', 'ssh -p ');
define('OTRA_CLI_COMMAND_MKDIR', 'mkdir');
define('OTRA_CLI_COMMAND_RECURSIVE_MKDIR', 'mkdir -p ');

// **** Checking the deployment config parameters ****
if (isset(AllConfig::$deployment) === false)
{
  echo CLI_RED . 'You have not defined deployment configuration.', END_COLOR, PHP_EOL;
  throw new OtraException('', 1, '', NULL, [], true);
}

$deploymentParameters = ['server', 'port', 'folder', 'privateSshKey', 'gcc'];

foreach($deploymentParameters as &$deploymentParameter)
{
  if (isset(AllConfig::$deployment[$deploymentParameter]) === false)
  {
    echo CLI_RED . 'You have not defined the ' . $deploymentParameter . ' in deployment configuration.', END_COLOR, PHP_EOL;
    throw new OtraException('', 1, '', NULL, [], true);
  }
}

define('OTRA_SUCCESS', CLI_GREEN . '  ✔  ' . END_COLOR . PHP_EOL);
$deployMask = (isset($argv[DEPLOY_ARG_MASK])) ? (int) $argv[DEPLOY_ARG_MASK] : 0;
$verbose = (isset($argv[DEPLOY_ARG_VERBOSE])) ? (int) $argv[DEPLOY_ARG_VERBOSE] : 0;
define(
  'DEPLOY_GCC_LEVEL_COMPILATION',
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
  $argv[GEN_BOOTSTRAP_ARG_VERBOSE] = $verbose; // if true, print warnings when the task fails
  require CONSOLE_PATH . 'deployment/genBootstrap/genBootstrapTask.php';
}

require CORE_PATH . 'tools/cli.php';

$buildDevMode = 0;

if (($deployMask & DEPLOY_MASK_JS_BEFORE_RSYNC) >> 1)
  $buildDevMode |= BUILD_DEV_MASK_TS;
elseif (($deployMask & DEPLOY_MASK_CSS_BEFORE_RSYNC) >> 2)
  $buildDevMode |= BUILD_DEV_MASK_SCSS;

if ($buildDevMode > 0)
{
  echo END_COLOR, 'Assets transcompilation...';

  // Generates all TypeScript (and CSS files ?) that belong to the project files, verbosity and gcc parameters took into account
  $result = cli('php bin/otra.php buildDev ' . $verbose . ' ' . $buildDevMode . ' ' . ((string)AllConfig::$deployment['gcc']));

  if ($result[0] === false)
  {
    echo CLI_RED . 'There was a problem during the assets transcompilation.';
    throw new OtraException('', 1, '', NULL, [], true);
  }

  echo OTRA_CLI_CONTROL_MODE . 3 . "D", OTRA_SUCCESS, $result[1], PHP_EOL;
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
  $result = cli('php bin/otra.php genAssets ' . $genAssetsMode . ' ' . DEPLOY_GCC_LEVEL_COMPILATION);

  if ($result[0] === false)
  {
    echo CLI_RED . 'There was a problem during the assets minification and compression.';
    throw new OtraException('', 1, '', NULL, [], true);
  }

  echo OTRA_CLI_CONTROL_MODE . 3 . "D", OTRA_SUCCESS, $result[1], PHP_EOL;
}

// Deploy the files on the server...
[
  'server' => $server,
  'port' => $port,
  'folder' => $folder,
  'privateSshKey' => $privateSshKey
] = AllConfig::$deployment;

echo PHP_EOL, 'Deploys the files on the server ', CLI_LIGHT_BLUE, $server, ':', $port, END_COLOR, ' in ',
CLI_LIGHT_BLUE, $folder . ' ...', END_COLOR, PHP_EOL;

/* --delete allows to delete things that are no present anymore on the source to keep a really synchronized folder
 * -P It combines the flags –progress and –partial.
 * The first of these gives you a progress bar for the transfers.
 * The second allows you to resume interrupted transfers.
 * -u => 'update' to not send files that are older than those in the server
 * -r is for recursive
 * -R is for --relative, it will create missing folders
 * -m remove empty folders */

$startCommand = 'rsync -zaruvhP --delete --progress -e \'ssh -i ' . $privateSshKey . ' -p ' . $port;
$cursorUpOne = "\033[1A";
//$cursorBackFour = "\033[4D";

$handleTransfer = function ($message, $command, string $operation = 'rsync') use(&$verbose, &$cursorUpOne)
{
  echo $message, ' ...', PHP_EOL;

  /* TODO Fix the verbosity by showing the progress of rsync commands via proc_open */
  if ($verbose === 1)
  {
    $result = cli($command);
//    echo PHP_EOL;
//    $result = [cliStream($cacheCommand), ''];
  } else
    $result = cli($command);

  if ($result[0] !== 0)
  {
    echo CLI_RED, 'Error when using ' . $operation . ' command.', END_COLOR, PHP_EOL;
    throw new OtraException('', 1, '', NULL, [], true);
  }

  /* TODO adapt the code for verbosity === 1 when we succeed to use proc_open without asking n times for a passphrase,
   * TODO showing things correctly and knowing when all that ends. */
  if ($verbose === 1)
    echo $cursorUpOne, OTRA_CLI_CONTROL_MODE . strlen($message) . "C ", OTRA_SUCCESS;
  else
    echo $cursorUpOne, OTRA_CLI_CONTROL_MODE . strlen($message) . "C", OTRA_SUCCESS;
};

$handleTransfer(
  'Sending cache',
  $startCommand . '\' cache/ ' . $server . ':' . $folder . '/cache/'
);

$preloadFilename = 'preload.php';

if (file_exists(BASE_PATH . $preloadFilename) === true)
  $handleTransfer(
    'Sending preload file',
    $startCommand . '\' ' .  $preloadFilename . ' ' . $server . ':' . $folder . '/' . $preloadFilename
  );

$handleTransfer(
  'Sending web folder',
  $startCommand . '\' web/ ' . $server . ':' . $folder . '/web/'
);

$handleTransfer(
  'Creating the config folder',
  OTRA_CLI_COMMAND_SSH_AND_PORT . $port . ' ' . $server . OTRA_CLI_COMMAND_RECURSIVE_MKDIR . $folder . '/config',
  OTRA_CLI_COMMAND_MKDIR
);

$handleTransfer(
  'Adding the OTRA constants',
  $startCommand . '\' config/prodConstants.php ' . $server . ':' . $folder . '/config/constants.php'
);

$handleTransfer(
  'Creating the vendor folder',
  OTRA_CLI_COMMAND_SSH_AND_PORT . $port . ' ' . $server . OTRA_CLI_COMMAND_RECURSIVE_MKDIR . $folder . '/vendor',
  OTRA_CLI_COMMAND_MKDIR
);

$handleTransfer(
  'Sending the OTRA templating engine, the translate tool and the production controller',
  $startCommand .
  '\' --delete-excluded -m --include=\'otra/otra/src/entryPoint.php\' --include=\'otra/otra/src/tools/translate.php\'' .
  ' --include=\'otra/otra/src/blocks.php\' --include=\'otra/otra/src/prod/ProdControllerTrait.php\'' .
  ' --include=\'*/\' --exclude=\'*\' vendor/ ' . $server . ':' . $folder .
  '/vendor/'
);

$handleTransfer(
  'Creating the bundles folder',
  OTRA_CLI_COMMAND_SSH_AND_PORT . $port . ' ' . $server . OTRA_CLI_COMMAND_RECURSIVE_MKDIR . $folder . '/bundles',
  OTRA_CLI_COMMAND_MKDIR
);

define('STRLEN_BASEPATH', strlen(BASE_PATH));

/**
 * See which files to send and which files to keep
 *
 * @param string $folderToAnalyze
 */
$seekingToSendFiles = function (string &$folderToAnalyze) use (&$handleTransfer, &$seekingToSendFiles, &$startCommand, &$folder, &$port, &$server)
{
  $bundleFolders = new \DirectoryIterator($folderToAnalyze);

  foreach ($bundleFolders as $bundleFolder)
  {
    if ($bundleFolder->isDot())
      continue;

    $folderFilename = $bundleFolder->getFilename();

    if (in_array($folderFilename, ['config',  'resources', 'controllers', 'services']) === true)
      continue;

    $folderRealPath = $bundleFolder->getRealPath();
    $folderRelativePath = substr($folderRealPath, STRLEN_BASEPATH);

    if ($folderFilename === 'views')
    {
      $handleTransfer(
        'Sending ' . $folderRelativePath . ' folder',
        $startCommand .
        '\' -m  ' . $folderRealPath . '/ ' . $server . ':' . $folder . '/' . $folderRelativePath
      );
      continue;
    }

    // Ensuring the folder exists before creating stuff inside
    $handleTransfer(
      'Creating the ' . $folderRelativePath .' folder',
      OTRA_CLI_COMMAND_SSH_AND_PORT . $port . ' ' . $server . OTRA_CLI_COMMAND_RECURSIVE_MKDIR . $folder . '/' . $folderRelativePath,
      OTRA_CLI_COMMAND_MKDIR
    );

    // Then we create the inside stuff
    $seekingToSendFiles($folderRealPath);
  }
};

$mainBundlesFolder = BASE_PATH . 'bundles';
$seekingToSendFiles($mainBundlesFolder);

$handleTransfer(
  'Checking log folder',
  $startCommand . '\' --include=\'*/\' --exclude=\'*\' logs/ ' . $server . ':' . $folder . '/logs/'
);
