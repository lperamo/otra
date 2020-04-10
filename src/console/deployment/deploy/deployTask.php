<?php
/** Production deployment task
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace src\console;

use config\AllConfig;
use \src\OtraException;

define('DEPLOY_ARG_MODE', 2);
define('DEPLOY_ARG_VERBOSE', 3);
define('DEPLOY_MODE_ONLY_RSYNC', 0);
define('DEPLOY_MODE_PHP_BEFORE_RSYNC', 1);
define('DEPLOY_MODE_PHP_JS_BEFORE_RSYNC', 2);
define('DEPLOY_MODE_PHP_JS_CSS_BEFORE_RSYNC', 3);

// **** Checking the deployment config parameters ****
if (isset(AllConfig::$deployment) === false)
{
  echo CLI_RED . 'You have not defined deployment configuration.', END_COLOR, PHP_EOL;
  throw new OtraException('', 1, '', NULL, [], true);
}

$deploymentParameters = ['server', 'port', 'folder', 'privateSshKey', 'gcc'];

foreach($deploymentParameters as $deploymentParameter)
{
  if (isset(AllConfig::$deployment[$deploymentParameter]) === false)
  {
    echo CLI_RED . 'You have not defined the ' . $deploymentParameter . ' in deployment configuration.', END_COLOR, PHP_EOL;
    throw new OtraException('', 1, '', NULL, [], true);
  }
}

$mode = (isset($argv[DEPLOY_ARG_MODE]) === true) ? (int) $argv[DEPLOY_ARG_MODE] : 0;
$verbose = (isset($argv[DEPLOY_ARG_VERBOSE]) === true) ? (int) $argv[DEPLOY_ARG_VERBOSE] : 0;

if ($mode > DEPLOY_MODE_ONLY_RSYNC)
{
  // We generate the class mapping...
  require CORE_PATH . 'console/deployment/genClassMap/genClassMapTask.php';

  echo 'Launching routes update...', PHP_EOL;
  require CORE_PATH . 'console/deployment/updateConf/updateConfTask.php';

  // bootstraps
  $argv[2] = 0; // prevents the class mapping
  $argv[3] = $verbose; // if true, print warnings when the task fails
  require CORE_PATH . 'console/deployment/genBootstrap/genBootstrapTask.php';
}

require CORE_PATH . 'tools/cli.php';


/**
 * @param int $verbose
 * @param int $mode 2 for TypeScript, 3 for TypeScript and CSS
 *
 * @throws OtraException
 */
function launchAssetsGeneration(int $verbose, int $mode = 3)
{
  // Generates all TypeScript (and CSS files ?) that belong to the project files, verbosity and gcc parameters took into account
  $result = cli('php bin/otra.php buildDev ' . $verbose . ' ' . $mode . ' ' . ((string) AllConfig::$deployment['gcc']));

  if ($result[0] === false)
  {
    echo CLI_RED . 'There was a problem during the assets generation.';
    throw new \src\OtraException('', 1, '', NULL, [], true);
  }
  echo END_COLOR, 'Assets generation...', PHP_EOL, $result[1], PHP_EOL;
}

if ($mode === DEPLOY_MODE_PHP_JS_BEFORE_RSYNC)
  launchAssetsGeneration($verbose, 2);
elseif ($mode === DEPLOY_MODE_PHP_JS_CSS_BEFORE_RSYNC)
  launchAssetsGeneration($verbose);

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
$success =  CLI_GREEN . '  ✔  ' . END_COLOR . PHP_EOL;

$handleTransfer = function ($message, $command, string $operation = 'rsync') use(&$verbose, &$success, &$cursorUpOne)
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

  $error = '';

  if ($result[0] !== 0)
  {
    echo CLI_RED, 'Error when using ' . $operation . ' command.', END_COLOR, PHP_EOL;
    throw new \src\OtraException('', 1, '', NULL, [], true);
  }

  /* TODO adapt the code for verbosity === 1 when we succeed to use proc_open without asking n times for a passphrase,
   * TODO showing things correctly and knowing when all that ends. */
  if ($verbose === 1)
    echo $cursorUpOne, "\033[" . strlen($message) . "C", $success;
  else
    echo $cursorUpOne, "\033[" . strlen($message) . "C", $success;
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
  'Creating the vendor folder',
  'ssh -p ' . $port . ' ' . $server . ' mkdir -p ' . $folder . '/vendor',
  'mkdir'
);

$handleTransfer(
  'Sending OTRA templating engine',
  $startCommand .
  '\' --delete-excluded -m --include=\'otra/otra/src/entryPoint.php\' --include=\'otra/otra/src/blocks.php\' --include=\'*/\' --exclude=\'*\' vendor/ ' . $server . ':' . $folder . '/vendor/'
);

$handleTransfer(
  'Creating the bundles folder',
  'ssh -p ' . $port . ' ' . $server . ' mkdir -p ' . $folder . '/bundles',
  'mkdir'
);

define('STRLEN_BASEPATH', strlen(BASE_PATH));

/**
 * @param string $folderToCheck
 */
$check = function (string &$folderToCheck) use (&$handleTransfer, &$check, &$startCommand, &$folder, &$port, &$server)
{
  $bundleFolders = new \DirectoryIterator($folderToCheck);

  foreach ($bundleFolders as $bundleFolder)
  {
    if ($bundleFolder->isDot())
      continue;

    $folderFilename = $bundleFolder->getFilename();

    if (in_array($folderFilename, ['config',  'resources', 'controllers']) === true)
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
      'ssh -p ' . $port . ' ' . $server . ' mkdir -p ' . $folder . '/' . $folderRelativePath,
      'mkdir'
    );

    // Then we create the inside stuff
    $check($folderRealPath);
  }
};

$mainBundlesFolder = BASE_PATH . 'bundles';
$check($mainBundlesFolder);

$handleTransfer(
  'Checking log folder',
  $startCommand . '\' --include=\'*/\' --exclude=\'*\' logs/ ' . $server . ':' . $folder . '/logs/'
);
