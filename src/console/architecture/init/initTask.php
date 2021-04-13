<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */

echo 'Initializing the project...', PHP_EOL;

// ********** CONFIGURATION FILES **********
echo 'Copying configuration files...';
define('OTRA_CONFIG_FOLDER', BASE_PATH . 'config/');

require CORE_PATH . 'tools/copyFilesAndFolders.php';

copyFileAndFolders(
  [
    CORE_PATH . 'init/config',
    CORE_PATH . 'init/tsconfig.json.dist',
    CORE_PATH . 'init/.eslintrc.json.dist'
  ],
  [
    OTRA_CONFIG_FOLDER,
    BASE_PATH . 'tsconfig.json.dist',
    BASE_PATH . '.eslintrc.json.dist'
  ]
);

$distFiles = [
  OTRA_CONFIG_FOLDER . 'dev/AllConfig.php.dist',
  OTRA_CONFIG_FOLDER . 'prod/AllConfig.php.dist',
  OTRA_CONFIG_FOLDER . 'AdditionalClassFiles.php.dist',
  OTRA_CONFIG_FOLDER . 'AllConfig.php.dist',
  OTRA_CONFIG_FOLDER . '.htaccess.dist',
  OTRA_CONFIG_FOLDER . 'Routes.php.dist',
  BASE_PATH . 'tsconfig.json.dist',
  BASE_PATH . '.eslintrc.json.dist'
];

foreach ($distFiles as $distFile)
{
  $destinationFilePath = substr($distFile, 0, -5);

  // If the PHP version of the file already exists, we do not overwrite it.
  if (!file_exists($destinationFilePath))
    copy($distFile, $destinationFilePath);
}

// We need a routes configuration file even empty.
define('OTRA_BUNDLES_CONFIG_PATH', BASE_PATH . 'bundles/config/');

if (!file_exists(OTRA_BUNDLES_CONFIG_PATH))
  mkdir(OTRA_BUNDLES_CONFIG_PATH, 0777, true);

file_put_contents(OTRA_BUNDLES_CONFIG_PATH . 'Routes.php',
  '<?php declare(strict_types=1); return [];');

echo CLI_BOLD_LIGHT_GREEN, ' ✔', END_COLOR, PHP_EOL;

// ********** WEB FOLDER FILES **********
echo 'Adding the files for the web folder...';

$webFolder = BASE_PATH . 'web/';
const OTRA_INDEX_FILENAME  = 'index.php';
const OTRA_INDEX_DEV_FILE_NAME = 'indexDev.php';
const OTRA_LOAD_STATIC_ROUTE = 'loadStaticRoute.php';
const CORE_PATH_INIT_WEB_FOLDER = CORE_PATH . 'init/web/';

copyFileAndFolders(
  [
    CORE_PATH_INIT_WEB_FOLDER . OTRA_INDEX_FILENAME,
    CORE_PATH_INIT_WEB_FOLDER . OTRA_INDEX_DEV_FILE_NAME,
    CORE_PATH_INIT_WEB_FOLDER . OTRA_LOAD_STATIC_ROUTE
  ],
  [
    $webFolder . OTRA_INDEX_FILENAME,
    $webFolder . OTRA_INDEX_DEV_FILE_NAME,
    $webFolder . OTRA_LOAD_STATIC_ROUTE
  ]
);

echo CLI_BOLD_LIGHT_GREEN, ' ✔', END_COLOR, PHP_EOL;

// ********** LOGS FOLDER FILES **********
echo 'Adding the base architecture for the logs...';

// Creating log folders
define('OTRA_LOGS_PATH', BASE_PATH . 'logs/');
define('OTRA_LOGS_DEV_PATH', OTRA_LOGS_PATH . 'dev/');
define('OTRA_LOGS_PROD_PATH', OTRA_LOGS_PATH . 'prod/');

if (!file_exists(OTRA_LOGS_DEV_PATH))
  mkdir(OTRA_LOGS_DEV_PATH, 0777, true);

if (!file_exists(OTRA_LOGS_PROD_PATH))
  mkdir(OTRA_LOGS_PROD_PATH);

// Creating log files
define('OTRA_LOG_FILES_PATH', [
  OTRA_LOGS_DEV_PATH . 'sql.txt',
  OTRA_LOGS_DEV_PATH . 'trace.txt',
  OTRA_LOGS_PROD_PATH . 'classNotFound.txt',
  OTRA_LOGS_PROD_PATH . 'unknownExceptions.txt',
  OTRA_LOGS_PROD_PATH . 'unknownFatalErrors.txt'
]);

foreach (OTRA_LOG_FILES_PATH as $logFile)
{
  if (!file_exists($logFile))
    touch($logFile);

  // Force the rights mode in order to be sure to be able to overwrite the file.
  chmod($logFile, 0666);
}

echo CLI_BOLD_LIGHT_GREEN, ' ✔', END_COLOR, PHP_EOL, PHP_EOL;

// ********** GENERATE TASK METADATA **********
require CONSOLE_PATH . 'helpAndTools/generateTaskMetadata/generateTaskMetadataTask.php';

echo PHP_EOL,
  'If you are on some unix distribution, you can add the following line to your profile to have a shortcut to OTRA binary',
  PHP_EOL;
echo CLI_LIGHT_CYAN, 'alias otra="php bin/otra.php"', END_COLOR, PHP_EOL, PHP_EOL;

echo 'If you want to see an example application, type ', CLI_LIGHT_CYAN, 'otra createHelloWorld'. END_COLOR, '.',
  PHP_EOL;
