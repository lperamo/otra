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
    CORE_PATH . 'init/config'
  ],
  [
    $configFolder
  ]
);

foreach (glob($configFolder . '{**/,}{**,.htaccess}.dist', GLOB_BRACE) as $distFile)
{
  $destinationFilePath = substr($distFile, 0, -5);

  // If the PHP version of the file already exists, we do not overwrite it.
  if (false === file_exists($destinationFilePath))
    copy($distFile, $destinationFilePath);
}

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
  CORE_PATH . 'init/web/favicon.png' => $webFolder . 'favicon.png',
  CORE_PATH . 'init/web/404.php' => $webFolder . '404.php'
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

echo CLI_BOLD_LIGHT_GREEN, ' ✔', END_COLOR, PHP_EOL, PHP_EOL;

echo 'If you are on some unix distribution, you can add the following line to your profile to have a shortcut to OTRA binary',
  PHP_EOL;
echo CLI_LIGHT_CYAN, 'alias otra="php bin/otra.php ""$@""', END_COLOR,
  PHP_EOL, PHP_EOL;
echo 'If you want to see an example application, type \'otra createHelloWorld\'.', PHP_EOL;
