<?php

echo 'Initializing the project...', PHP_EOL;

// ********** CONFIGURATION FILES **********
echo 'Copying configuration files...';

$configFolder = BASE_PATH . 'config/';
$devConfigFolder = $configFolder . 'dev/';
$prodConfigFolder = $configFolder . 'prod/';

require CORE_PATH . 'tools/copyFilesAndFolders.php';

copyFileAndFolders(
  [
    CORE_PATH . 'init/config',
    CORE_PATH . 'init/tsconfig.json.dist',
    CORE_PATH . 'init/tslint.json.dist'
  ],
  [
    $configFolder,
    BASE_PATH . 'tsconfig.json.dist',
    BASE_PATH . 'tslint.json.dist'
  ]
);

$distFiles = [
  $configFolder . 'dev/AllConfig.php.dist',
  $configFolder . 'prod/AllConfig.php.dist',
  $configFolder . 'AdditionalClassFiles.php.dist',
  $configFolder . 'AllConfig.php.dist',
  $configFolder . '.htaccess.dist',
  $configFolder . 'Routes.php.dist',
  BASE_PATH . 'tsconfig.json.dist',
  BASE_PATH . 'tslint.json.dist'
];

foreach ($distFiles as $distFile)
{
  $destinationFilePath = substr($distFile, 0, -5);

  // If the PHP version of the file already exists, we do not overwrite it.
  if (false === file_exists($destinationFilePath))
    copy($distFile, $destinationFilePath);
}

// We need a routes configuration file even empty.
$bundleConfigPath = BASE_PATH . 'bundles/config/';

if (file_exists($bundleConfigPath) === false)
  mkdir($bundleConfigPath, 0777, true);

file_put_contents($bundleConfigPath . 'Routes.php', '<?php return []; ?>');

echo CLI_BOLD_LIGHT_GREEN, ' ✔', END_COLOR, PHP_EOL;

// ********** WEB FOLDER FILES **********
echo 'Adding the files for the web folder...';

$webFolder = BASE_PATH . 'web/';
$indexFileName = 'index.php';
$indexDevFileName = 'indexDev.php';

copyFileAndFolders(
  [
    CORE_PATH . 'init/web/' . $indexFileName,
    CORE_PATH . 'init/web/'. $indexDevFileName
  ],
  [
    $webFolder . $indexFileName,
    $webFolder . $indexDevFileName
  ]
);

// The favicons and the 404.php page should be customisable and we will not overwrite those files.
$webCustomisableFiles = [
  CORE_PATH . 'init/web/favicon.ico' => $webFolder . 'favicon.ico',
  CORE_PATH . 'init/web/favicon.png' => $webFolder . 'favicon.png'
];

foreach ($webCustomisableFiles as $sourceFile => &$destinationFile)
{
  if (false === file_exists($destinationFile))
    copyFileAndFolders([$sourceFile], [$destinationFile]);
}

echo CLI_BOLD_LIGHT_GREEN, ' ✔', END_COLOR, PHP_EOL;

// ********** LOGS FOLDER FILES **********
echo 'Adding the base architecture for the logs...';

$logsPath = BASE_PATH . 'logs/';
$logsDevPath = $logsPath . 'dev/';
$logsProdPath = $logsPath . 'prod/';

if (false === file_exists($logsDevPath))
  mkdir($logsDevPath, 0777, true);

if (false === file_exists($logsProdPath))
  mkdir($logsProdPath);

$sqlLogPath = $logsDevPath . 'sql.txt';

if (false === file_exists($sqlLogPath))
  touch($sqlLogPath);

$traceLogPath = $logsDevPath . 'trace.txt';

if (false === file_exists($traceLogPath))
  touch($traceLogPath);

// Force the rights mode in order to be sure to be able to overwrite the file.
chmod($sqlLogPath, 0666);

echo CLI_BOLD_LIGHT_GREEN, ' ✔', END_COLOR, PHP_EOL, PHP_EOL;

// ********** GENERATE TASK METADATA **********
require CORE_PATH . 'console/helpAndTools/generateTaskMetadata/generateTaskMetadataTask.php';

echo PHP_EOL,
  'If you are on some unix distribution, you can add the following line to your profile to have a shortcut to OTRA binary',
  PHP_EOL;
echo CLI_LIGHT_CYAN, 'alias otra="php bin/otra.php"', END_COLOR, PHP_EOL, PHP_EOL;

echo 'If you want to see an example application, type ', CLI_LIGHT_CYAN, 'otra createHelloWorld'. END_COLOR, '.',
  PHP_EOL;
