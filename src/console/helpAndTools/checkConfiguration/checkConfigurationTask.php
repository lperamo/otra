<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\helpAndTools
 */
declare(strict_types=1);

namespace otra\console\helpAndTools\checkConfiguration;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use otra\OtraException;
use const otra\cache\php\{BASE_PATH, BUNDLES_PATH};
use const otra\console\
{ADD_BOLD, CLI_BASE, CLI_ERROR, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, CLI_WARNING, END_COLOR, REMOVE_BOLD_INTENSITY};

if (!file_exists(BUNDLES_PATH) || !(new FilesystemIterator(BUNDLES_PATH))->valid())
{
  echo CLI_ERROR, 'There are no bundles to use!', END_COLOR, PHP_EOL;
  throw new OtraException(code: 1, exit: true);
}

const
  ROUTES_PHP_FILE_NAME = 'Routes.php',
  CHUNKS_KEY = 'chunks',
  RESOURCES_KEY = 'resources',
  CHUNKS_MAX_PARAMETERS_COUNT = 5,
  ROUTE_ALLOWED_PARAMETERS = [
    'bootstrap',
    CHUNKS_KEY,
    'get',
    'post',
    RESOURCES_KEY,
    'session'
  ],
  ROUTE_RESOURCES_ALLOWED_PARAMETERS = [
    'app_css',
    'app_js',
    'bundle_css',
    'bundle_js',
    'core_css',
    'core_js',
    'module_css',
    'module_js',
    'print_css',
    'template'
  ],
  ROUTE_RESOURCES_WITH_ARRAY = [
    'app_css',
    'app_js',
    'bundle_css',
    'bundle_js',
    'module_css',
    'module_js',
    'core_css',
    'core_js',
    'print_css'
  ],
  LABEL_YOUR_ROUTE_CONFIGURATION = 'Your route configuration ',
  LABEL_YOUR_ROUTE_PARAMETER = 'Your route parameter ',
  LABEL_IS_EMPTY = ' is empty!',
  LABEL_YOUR_ROUTE = 'Your route ',
  LABEL_MUST_BE_AN_ARRAY = ' must be an array.';

define(__NAMESPACE__ . '\\BASE_PATH_LENGTH', mb_strlen(BASE_PATH));
echo 'Checking routes configuration...' . PHP_EOL;

$dir_iterator = new RecursiveDirectoryIterator(BUNDLES_PATH, FilesystemIterator::SKIP_DOTS);

// SELF_FIRST to have file AND folders in order to detect addition of new files
$iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);

/** @var SplFileInfo $entry */
foreach($iterator as $entry)
{
  if ($entry->isDir()
    || $entry->getBasename() !== ROUTES_PHP_FILE_NAME
    || ($realPath = $entry->getRealPath()) === BUNDLES_PATH . 'config/' . ROUTES_PHP_FILE_NAME)
    continue;

  echo 'Analyzing the file ', ADD_BOLD, CLI_BASE, '[BASE_PATH]', REMOVE_BOLD_INTENSITY, CLI_INFO_HIGHLIGHT,
    substr($realPath, BASE_PATH_LENGTH), CLI_BASE, '...', END_COLOR, PHP_EOL;
  $routesContent = file_get_contents($realPath);
  $routesConfigurationErrors = 0;

  if (!str_contains($routesContent, 'declare(strict_types=1);'))
  {
    echo CLI_WARNING, 'You must put a ', CLI_INFO_HIGHLIGHT, 'declare(strict_types=1);', CLI_WARNING, ' declaration.',
      END_COLOR, PHP_EOL;
    ++$routesConfigurationErrors;
  }

  $routes = require $realPath;

  if (empty($routes))
  {
    echo CLI_WARNING, 'Your routes array is empty!', END_COLOR, PHP_EOL;
    ++$routesConfigurationErrors;
  }
  else
  {
    foreach ($routes as $route => $routeInformations)
    {
      if (!is_string($route))
      {
        echo CLI_WARNING, LABEL_YOUR_ROUTE, CLI_INFO_HIGHLIGHT, $route, CLI_WARNING, ' is not a string!', END_COLOR,
          PHP_EOL;
        ++$routesConfigurationErrors;
      }

      if (empty($routeInformations))
      {
        echo CLI_WARNING, LABEL_YOUR_ROUTE, CLI_INFO_HIGHLIGHT, $route, CLI_WARNING, LABEL_IS_EMPTY, END_COLOR, PHP_EOL;
        ++$routesConfigurationErrors;
      }

      foreach ($routeInformations as $routeParameter => $routeInformation)
      {
        if (!is_string($routeParameter))
        {
          echo CLI_WARNING, LABEL_YOUR_ROUTE_CONFIGURATION, CLI_INFO_HIGHLIGHT, $routeParameter, CLI_WARNING,
            ' is not a string!', END_COLOR, PHP_EOL;
          ++$routesConfigurationErrors;
        }

        if (!in_array($routeParameter, ROUTE_ALLOWED_PARAMETERS))
        {
          echo CLI_WARNING, LABEL_YOUR_ROUTE_PARAMETER, CLI_INFO_HIGHLIGHT, $routeParameter, CLI_WARNING,
            ' does not exist! It can be : ', implode(',', ROUTE_ALLOWED_PARAMETERS), '.', END_COLOR,
            PHP_EOL;
          ++$routesConfigurationErrors;
        }

        if (empty($routeInformation))
        {
          echo CLI_WARNING, LABEL_YOUR_ROUTE_CONFIGURATION, CLI_INFO_HIGHLIGHT, $routeParameter, CLI_WARNING,
            LABEL_IS_EMPTY, END_COLOR, PHP_EOL;
          ++$routesConfigurationErrors;
        }
        elseif (!is_array($routeInformation))
        {
          echo CLI_WARNING, LABEL_YOUR_ROUTE_CONFIGURATION, CLI_INFO_HIGHLIGHT, $routeParameter, CLI_WARNING,
            LABEL_MUST_BE_AN_ARRAY, END_COLOR, PHP_EOL;
          ++$routesConfigurationErrors;
        } else
        {
          $routeInfoParametersCount = count($routeInformation);

          if ($routeParameter === CHUNKS_KEY)
          {
            if ($routeInfoParametersCount !== CHUNKS_MAX_PARAMETERS_COUNT)
            {
              echo CLI_WARNING, LABEL_YOUR_ROUTE_CONFIGURATION, CLI_INFO_HIGHLIGHT, CHUNKS_KEY, CLI_WARNING,
                ' must have 5 parameters : url, bundle, module, controller and action. You currently have ',
                CLI_INFO_HIGHLIGHT, $routeInfoParametersCount, CLI_WARNING, ' parameters!', END_COLOR, PHP_EOL;
              ++$routesConfigurationErrors;
            }
          } elseif ($routeParameter === RESOURCES_KEY)
          {
            foreach ($routeInformation as $resourceParameter => $parametersInformation)
            {
              if (!in_array($resourceParameter, ROUTE_RESOURCES_ALLOWED_PARAMETERS))
              {
                echo CLI_WARNING, LABEL_YOUR_ROUTE_PARAMETER, CLI_INFO_HIGHLIGHT, RESOURCES_KEY, CLI_WARNING,
                  ' contains a parameter ', CLI_INFO_HIGHLIGHT, $resourceParameter, CLI_WARNING,
                  ' that does not exist! It can be : ', implode(',', ROUTE_RESOURCES_ALLOWED_PARAMETERS),
                  '.', END_COLOR, PHP_EOL;
                ++$routesConfigurationErrors;
              }

              if (in_array($resourceParameter, ROUTE_RESOURCES_WITH_ARRAY) && !is_array($parametersInformation))
              {
                echo CLI_WARNING, 'Your route resources parameter ', CLI_INFO_HIGHLIGHT, $resourceParameter, CLI_WARNING,
                  LABEL_MUST_BE_AN_ARRAY, END_COLOR, PHP_EOL;
                ++$routesConfigurationErrors;
              }
            }
          }
        }
      }
    }
  }

  if ($routesConfigurationErrors > 0)
    echo CLI_WARNING, 'You have ', CLI_INFO_HIGHLIGHT, $routesConfigurationErrors, CLI_WARNING,
      ($routesConfigurationErrors > 1 ? ' warnings/errors' : ' warning/error'), ' in your routes configuration.';
  else
    echo CLI_BASE, 'You do not have any problems in your routes configuration', CLI_SUCCESS, ' ✔', END_COLOR, PHP_EOL;
}
