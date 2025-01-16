<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\architecture
 */
declare(strict_types=1);

namespace otra\console\architecture\init;

use otra\OtraException;
use const otra\cache\php\{BASE_PATH, BUNDLES_PATH, CACHE_PATH, CONSOLE_PATH, CORE_PATH};
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, ERASE_SEQUENCE, END_COLOR};
use function otra\console\helpAndTools\generateTaskMetadata\generateTaskMetadata;
use function otra\tools\copyFileAndFolders;

const
  OTRA_CONFIG_FOLDER = BASE_PATH . 'config/',
  OTRA_BUNDLES_CONFIG_PATH = BUNDLES_PATH . 'config/',
  OTRA_INDEX_FILENAME  = 'index.php',
  OTRA_INDEX_DEV_FILE_NAME = 'indexDev.php',
  OTRA_LOAD_STATIC_ROUTE = 'loadStaticRoute.php',
  CORE_PATH_INIT_WEB_FOLDER = CORE_PATH . 'init/web/',
  OTRA_LOGS_PATH = BASE_PATH . 'logs/',
  OTRA_LOGS_DEV_PATH = OTRA_LOGS_PATH . 'dev/',
  OTRA_LOGS_PROD_PATH = OTRA_LOGS_PATH . 'prod/',
    OTRA_LOG_FILES_PATH = [
    OTRA_LOGS_DEV_PATH . 'sql.txt',
    OTRA_LOGS_DEV_PATH . 'trace.txt',
    OTRA_LOGS_PROD_PATH . 'log.txt',
    OTRA_LOGS_PROD_PATH . 'classNotFound.txt',
    OTRA_LOGS_PROD_PATH . 'unknownExceptions.txt',
    OTRA_LOGS_PROD_PATH . 'unknownFatalErrors.txt'
  ],
  OTRA_SESSIONS_PATH = CACHE_PATH . 'php/sessions/',
  OTRA_ROUTES_PATH = CACHE_PATH . 'php/otraRoutes/';

/**
 * @throws OtraException
 * @return void
 */
function init() : void
{
  echo 'Initializing the project...', PHP_EOL;

  // ********** CONFIGURATION FILES **********
  echo 'Copying configuration files...', PHP_EOL;

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

  // We need a routes' configuration file even empty.
  if (!file_exists(OTRA_BUNDLES_CONFIG_PATH))
    mkdir(OTRA_BUNDLES_CONFIG_PATH, 0777, true);

  file_put_contents(OTRA_BUNDLES_CONFIG_PATH . 'Routes.php',
    '<?php declare(strict_types=1); return [];');

  echo ERASE_SEQUENCE, 'Configuration files copied ', CLI_SUCCESS, ' ✔', END_COLOR, PHP_EOL;

  // ********** WEB FOLDER FILES **********
  echo 'Adding the files for the web folder...', PHP_EOL;

  $webFolder = BASE_PATH . 'web/';
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

  echo ERASE_SEQUENCE, CLI_BASE, 'Files added to the web folder ', CLI_SUCCESS, ' ✔', END_COLOR, PHP_EOL;

  // ********** LOGS FOLDER FILES **********
  echo 'Adding the base architecture for the logs...', PHP_EOL;

  // Creating log folders

  if (!file_exists(OTRA_LOGS_DEV_PATH))
    mkdir(OTRA_LOGS_DEV_PATH, 0777, true);

  if (!file_exists(OTRA_LOGS_PROD_PATH))
    mkdir(OTRA_LOGS_PROD_PATH);

  // Creating log files
  foreach (OTRA_LOG_FILES_PATH as $logFile)
  {
    if (!file_exists($logFile))
      touch($logFile);

    // Force the rights' mode to be sure to be able to overwrite the file.
    chmod($logFile, 0666);
  }

  echo ERASE_SEQUENCE, 'Base architecture for the logs added', CLI_SUCCESS, ' ✔', END_COLOR, PHP_EOL, PHP_EOL;

  // Check if the sessions' folder exists
  if (!file_exists(OTRA_SESSIONS_PATH) && !mkdir(OTRA_SESSIONS_PATH, 0777, true))
  {
    echo CLI_ERROR, 'Cannot create the folder ', CLI_INFO_HIGHLIGHT, OTRA_SESSIONS_PATH, CLI_ERROR, '.', PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }

  // Checking that the 'init' folder in the cache/php folder exists
  if (!file_exists(OTRA_ROUTES_PATH))
    mkdir(OTRA_ROUTES_PATH, 0777, true);

  // ********** GENERATE TASK METADATA **********
  require CONSOLE_PATH . 'helpAndTools/generateTaskMetadata/generateTaskMetadataTask.php';
  generateTaskMetadata();
}
