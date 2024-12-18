<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\deployment
 */
declare(strict_types=1);

namespace otra\console\deployment\genAssets;

use FilesystemIterator;
use JsonException;
use otra\config\Routes;
use otra\OtraException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionException;
use RuntimeException;
use SplFileInfo;
use const otra\cache\php\
{BASE_PATH, BUNDLES_PATH, CACHE_PATH, CONSOLE_PATH, CORE_PATH, DEV_SRI, DIR_SEPARATOR, PROD_SRI};
use const otra\config\VERSION;
use const otra\console\
{CLI_BASE, CLI_ERROR, CLI_GRAY, CLI_INFO, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, CLI_WARNING, END_COLOR};
use function otra\console\convertLongArrayToShort;
use function otra\src\console\deployment\googleClosureCompile;
use function otra\tools\brotliCompressFile;
use function otra\tools\files\returnLegiblePath;

const
  GEN_ASSETS_ARG_ASSETS_MASK = 2,
  GEN_ASSETS_ARG_ROUTE = 4,
  COMPRESSION_LEVEL = 9,
  
  GEN_ASSETS_MASK_TEMPLATE = 1,
  GEN_ASSETS_MASK_CSS = 2,
  GEN_ASSETS_MASK_JS = 4,
  GEN_ASSETS_MASK_MANIFEST = 8,
  GEN_ASSETS_MASK_SVG = 16,
  GEN_ASSETS_MASK_TOTAL = 31,
  
  OTRA_UNLINK_CALLBACK = 'unlink',
  OTRA_CLI_INFO_STRING = 'CLI_INFO',
  OTRA_LABEL_TSCONFIG_JSON = 'tsconfig.json',
  SRI_DEV_FULL_PATH = CACHE_PATH . 'devSri.php',
  SRI_PROD_FULL_PATH = CACHE_PATH . 'prodSri.php',
  SRI_FILE_BEGINNING = '<?php declare(strict_types=1);namespace otra\cache\php;const ',
  KEY_CSS = 'css',
  KEY_JS = 'js',
  KEY_PREFIX = 0,
  KEY_PATH = 1;
/**
 * @param string $folder  The resource folder name
 * @param string $shaName The route's name encoded in sha1
 */
function unlinkResourceFile(string $folder, string $shaName) : void
{
  $fileName = CACHE_PATH . $folder . $shaName . '.br';

  if (file_exists($fileName))
    unlink($fileName);
}

function status(string $status, string $color = 'CLI_SUCCESS') : string
{
  return ' [' . constant('otra\\console\\' . $color) . $status . CLI_GRAY. ']';
}

function getSRIHash(string $absolutePath) : string
{
  return base64_encode(hash_file('sha384', $absolutePath, true));
}

/**
 * @param string $assetType 'css' or 'js'
 *
 * @return array<null|string, array> Return the path of the 'macro' resource file
 */
function loadAndSaveResources(
  array $resources,
  array $routeChunks,
  string $assetType,
  string $bundlePath,
  string $shaName,
  string $routeName,
  array $devSriHashes
) : array
{
  $allResourcesParts = [];
  $resourceVariants = [
    [
      KEY_PREFIX => 'app_',
      KEY_PATH => BASE_PATH . 'bundles/'
    ],
    [
      KEY_PREFIX => 'bundle_',
      KEY_PATH => $bundlePath
    ],
    [
      KEY_PREFIX => 'core_',
      KEY_PATH => CORE_PATH
    ],
    [
      KEY_PREFIX => 'module_',
      KEY_PATH => $bundlePath . $routeChunks[Routes::ROUTES_CHUNKS_MODULE] . DIR_SEPARATOR
    ]
  ];

  foreach ($resourceVariants as $variant)
  {
    [$returnedContent, $devSriHashes] = loadResource(
      $resources,
      $routeChunks,
      $variant[KEY_PREFIX] . $assetType,
      $variant[KEY_PATH],
      $routeName,
      $devSriHashes
    );

    if ($returnedContent !== null)
      $allResourcesParts[] = $returnedContent;
  }

  // If there were no resources to optimize
  if (empty($allResourcesParts))
    return [null, $devSriHashes];

  // Combine all resources efficiently
  $allResources = implode('', $allResourcesParts);

  $resourceFolderPath = CACHE_PATH . $assetType . DIR_SEPARATOR;
  $pathAndFile = $resourceFolderPath . $shaName . ($assetType === KEY_JS ? '_' : '');

  if (!is_dir($resourceFolderPath) && !mkdir($resourceFolderPath, 0755, true))
    throw new RuntimeException('Failed to create directory: ' . $resourceFolderPath);

  if (file_put_contents($pathAndFile, $allResources) === false)
    throw new RuntimeException('Failed to write to file: ' . $pathAndFile);

  return [$pathAndFile, $devSriHashes];
}

/**
 * Loads CSS or JS resources
 *
 * @param string $variantKey app_js, module_css kind of ...
 *
 * @return ?string[] Returns an array with the content and the final path, or null if the resource doesn't exist
 */
function loadResource(array $resources,
  array $chunks,
  string $variantKey,
  string $bundlePath,
  string $routeName,
  array $devSriHashes,
  ?string $resourcePath = null)
: ?array
{
  // If this kind of resource does not exist, we leave
  if (!isset($resources[$variantKey]))
    return [null, $devSriHashes];

  $resourceType = substr(strrchr($variantKey, '_'), 1);
  $resourcePath = $bundlePath . ($resourcePath ?? '') . 'resources/' . $resourceType . DIR_SEPARATOR;
  $content = $finalContent = '';

  foreach ($resources[$variantKey] as $resourceKey => $resource)
  {
    $resourceName = is_array($resource) ? $resourceKey : $resource;

    if (!str_contains($resourceName, 'http:'))
    {
      $finalPath = $resourcePath . $resourceName . '.' . $resourceType;

      if (!file_exists($finalPath))
      {
        require_once CORE_PATH . 'tools/files/returnLegiblePath.php';
        echo CLI_WARNING, 'This file does not exist: ', returnLegiblePath($finalPath), CLI_BASE;
        return [null, $devSriHashes];
      }

      $content = file_get_contents($finalPath);
    } else
    {
      $curlHandle = curl_init();
      $finalPath = $resourceName . '.' . $resourceType;
      curl_setopt_array($curlHandle, [
        CURLOPT_URL => $finalPath,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
      ]);
      $content = curl_exec($curlHandle);
      curl_close($curlHandle);

      if ($content === false)
      {
        echo CLI_WARNING, 'No content retrieved! for ', $finalPath, CLI_BASE, PHP_EOL;
        return [null, $devSriHashes];
      }
    }

    if ($resourceType === KEY_CSS)
    {
      // Minify CSS: remove unnecessary spaces and line breaks
      $content .= str_replace(
        ['  ', ' :', ': ', ', ', ' {', '{ ', '; ', PHP_EOL],
        [' ', ':', ':', ',', '{', '{', ';', ''],
        $content
      );
    }

    // Handle the workaround for Google Closure Compiler
    if (str_ends_with($content, 'p'))
      $content .= PHP_EOL;

    $finalContent.= $content;
    $sriFilePath = (str_contains($finalPath, 'https::'))
      ? $finalPath
      : 'l:' . substr($finalPath, strlen(BASE_PATH));

    $devSriHashes[$routeName][$resourceType][$sriFilePath] = getSRIHash($finalPath);
  }

  return ($finalContent === ''
    ? [null, $devSriHashes] // Return null explicitly if no resource matches
    : [$content, $devSriHashes]
  );
}

/**
 * @throws ReflectionException
 */
function saveSRIHashes(string $path, string $constant, array $hashes): void
{
  file_put_contents($path, SRI_FILE_BEGINNING . $constant . '=' . convertLongArrayToShort($hashes) . ';' . PHP_EOL);
}

/**
 * @param array<int, string> $argumentsVector Command-line arguments, similar to those provided by $argv.
 *
 * @throws JsonException|OtraException
 * @throws ReflectionException
 * @return void
 */
function genAssets(array $argumentsVector) : void
{
  if (!file_exists(BUNDLES_PATH))
  {
    echo CLI_ERROR, 'There are no bundles to use!', END_COLOR, PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }

  require_once BASE_PATH . 'config/AllConfig.php';
  // `require_once` needed because of the case of 'deploy' task that already launched the routes.
  require_once BASE_PATH . 'config/Routes.php';
  require CORE_PATH . 'tools/compression.php';

  define(__NAMESPACE__ . '\\STRLEN_BASE_PATH', strlen(BASE_PATH));
  define(
    __NAMESPACE__ . '\\JS_LEVEL_COMPILATION',
    [
      'WHITESPACE_ONLY',
      'SIMPLE_OPTIMIZATIONS',
      'ADVANCED_OPTIMIZATIONS'
    ][isset($argumentsVector[3]) ? (int) $argumentsVector[3] : 1]);

  $routes = Routes::$allRoutes;

  // Checking the mask parameter, 31 = defaults to all assets
  $assetsMask = $argumentsVector[GEN_ASSETS_ARG_ASSETS_MASK] ?? 31;
  if (!is_numeric($assetsMask))
  {
    echo CLI_ERROR, 'Invalid mask! Must be between ', GEN_ASSETS_MASK_TEMPLATE, ' and ', GEN_ASSETS_MASK_TOTAL, '.',
      END_COLOR;
    throw new OtraException(code: 1, exit: true);
  }
  define(__NAMESPACE__ . '\\ASSETS_MASK', $assetsMask + 0);

  define('GEN_ASSETS_TEMPLATE', ASSETS_MASK & GEN_ASSETS_MASK_TEMPLATE);
  define('GEN_ASSETS_CSS', (ASSETS_MASK & GEN_ASSETS_MASK_CSS) >> 1);
  define('GEN_ASSETS_JS', (ASSETS_MASK & GEN_ASSETS_MASK_JS) >> 2);
  define('GEN_ASSETS_MANIFEST', (ASSETS_MASK & GEN_ASSETS_MASK_MANIFEST) >> 3);
  define('GEN_ASSETS_SVG', (ASSETS_MASK & GEN_ASSETS_MASK_SVG) >> 4);

  // If we only need the manifest or the SVGs, skips the assets' generation loop
  if (
    ASSETS_MASK !== GEN_ASSETS_MASK_MANIFEST &&
    ASSETS_MASK !== GEN_ASSETS_MASK_SVG &&
    ASSETS_MASK !== (GEN_ASSETS_MASK_MANIFEST | GEN_ASSETS_MASK_SVG)
  )
  {
    if (file_exists(SRI_DEV_FULL_PATH))
    {
      require SRI_DEV_FULL_PATH;
      $devSriHashes = DEV_SRI;
    } else
      $devSriHashes = [];

    if (file_exists(SRI_PROD_FULL_PATH))
    {
      require SRI_PROD_FULL_PATH;
      $prodSriHashes = PROD_SRI;
    } else
      $prodSriHashes = [];

    // If we ask just for only one route
    if (isset($argumentsVector[GEN_ASSETS_ARG_ROUTE]))
    {
      $theRoute = $argumentsVector[GEN_ASSETS_ARG_ROUTE];

      if (!isset($routes[$theRoute]))
      {
        echo PHP_EOL, CLI_WARNING, 'This route does not exist !', END_COLOR, PHP_EOL;
        throw new OtraException(code: 1, exit: true);
      }

      echo 'Cleaning the resources cache...';

      $routes = [$theRoute => $routes[$theRoute]];

      /***************** CLEANING THE FILES *******************
       ******* specific to the route passed in parameter ******/
      $shaName = sha1('ca' . $theRoute . VERSION . 'che');

      if (GEN_ASSETS_TEMPLATE !== 0)
        unlinkResourceFile('tpl/', $shaName);

      if (GEN_ASSETS_CSS !== 0)
        unlinkResourceFile('css/', $shaName);

      if (GEN_ASSETS_JS !== 0)
        unlinkResourceFile('js/', $shaName);
    } else
    {
      echo PHP_EOL, 'Cleaning the resources cache...';

      // Clean obsolete routes
      foreach (array_keys($prodSriHashes) as $routeName)
      {
        if (!in_array($routeName, $routes, true))
          unset($prodSriHashes[$routeName], $devSriHashes[$routeName]);
      }

      if (GEN_ASSETS_TEMPLATE !== 0)
        array_map(OTRA_UNLINK_CALLBACK, glob(CACHE_PATH . 'tpl/*'));

      if (GEN_ASSETS_CSS !== 0)
        array_map(OTRA_UNLINK_CALLBACK, glob(CACHE_PATH . 'css/*'));

      if (GEN_ASSETS_JS !== 0)
        array_map(OTRA_UNLINK_CALLBACK, glob(CACHE_PATH . 'js/*'));
    }

    echo CLI_SUCCESS, ' OK', END_COLOR, PHP_EOL;

    /*************** PROCESSING THE ROUTES ************/

    $cptRoutes = count($routes);
    $routePlural = '';

    if ($cptRoutes > 1)
      $routePlural = 's';

    echo $cptRoutes, ' route', $routePlural, ' to process. Processing the route', $routePlural, ' ...', PHP_EOL,
    PHP_EOL;

    require_once CONSOLE_PATH . 'deployment/googleClosureCompile.php';
    require CORE_PATH . 'tools/cli.php';

    foreach($routes as $routeName => $route)
    {
      // Showing the route name
      echo CLI_INFO_HIGHLIGHT, str_pad($routeName, 25), CLI_GRAY;

      $shaName = sha1('ca' . $routeName . VERSION . 'che');

      if (!isset($route['resources']))
      {
        echo status('Nothing to do', OTRA_CLI_INFO_STRING),
          ' =>', CLI_SUCCESS, ' OK', END_COLOR, '[', CLI_INFO, $shaName, END_COLOR, ']', PHP_EOL;
        unset($prodSriHashes[$routeName], $devSriHashes[$routeName]);
        continue;
      }

      $resources = $route['resources'];
      $chunks = $route['chunks'];

      // the bundle is generally not used in the OTRA exception route
      if (!isset($chunks[Routes::ROUTES_CHUNKS_BUNDLE]))
      {
        echo ' [NOTHING TO DO (NOT IMPLEMENTED FOR THIS PARTICULAR ROUTE)]', '[',
        CLI_INFO, $shaName, END_COLOR, ']', PHP_EOL;
        unset($prodSriHashes[$routeName], $devSriHashes[$routeName]);
        continue;
      }

      $bundlePath = BASE_PATH . 'bundles/' . $chunks[Routes::ROUTES_CHUNKS_BUNDLE] . DIR_SEPARATOR;
      $noErrors = true;

      /***** CSS - GENERATES THE COMPRESSED CSS FILES (IF ASKED AND IF NEEDED TO) *****/
      if (GEN_ASSETS_CSS !== 0)
      {
        if (str_contains(implode(array_keys($resources)), KEY_CSS))
        {
          if (!isset($prodSriHashes[$routeName][KEY_CSS]))
            $prodSriHashes[$routeName][KEY_CSS] = $devSriHashes[$routeName][KEY_CSS] = [];

          [$pathAndFile, $devSriHashes] =
            loadAndSaveResources($resources, $chunks, KEY_CSS, $bundlePath, $shaName, $routeName, $devSriHashes);

          if ($pathAndFile !== null)
          {
            /* @var string $pathAndFile */
            $prodSriFilePath = (str_contains($pathAndFile, 'https::'))
              ? $pathAndFile
              : 'l:' . substr($pathAndFile, strlen(BASE_PATH));

            $prodSriHashes[$routeName][KEY_CSS][$prodSriFilePath . '.br'] = getSRIHash($pathAndFile);
            brotliCompressFile($pathAndFile, null, COMPRESSION_LEVEL);
            echo status('SCREEN CSS');
          }
          else
            echo status('NO SCREEN CSS', OTRA_CLI_INFO_STRING);

          [$printCss, $devSriHashes] = loadResource(
            $resources,
            $chunks,
            'print_css',
            $bundlePath . $chunks[Routes::ROUTES_CHUNKS_MODULE] . DIR_SEPARATOR,
            $routeName,
            $devSriHashes
          );

          if ($printCss === '' || $printCss === null)
            echo status('NO PRINT CSS', 'CLI_ERROR');
          else
          {
            $resourceFolderPath = CACHE_PATH . 'css/';
            $pathAndFile = $resourceFolderPath . 'print_' . $shaName;
            $prodSriFilePath = (str_contains($pathAndFile, 'https::'))
              ? $pathAndFile
              : 'l:' . substr($pathAndFile, strlen(BASE_PATH));

            if (!file_exists($resourceFolderPath))
              mkdir($resourceFolderPath, 0755, true);

            file_put_contents($pathAndFile, $printCss);

            $prodSriHashes[$routeName][KEY_CSS][$prodSriFilePath . '.br'] = getSRIHash($pathAndFile);
            brotliCompressFile($pathAndFile, null, COMPRESSION_LEVEL);
            echo status('PRINT CSS');
          }
        }
        else
          echo status('NO CSS', OTRA_CLI_INFO_STRING);
      }

      /***** JS - GENERATES THE COMPRESSED JS FILES (IF ASKED AND IF NEEDED TO) *****/
      if (GEN_ASSETS_JS !== 0)
      {
        if (str_contains(implode(array_keys($resources)), KEY_JS))
        {
          if (!isset($prodSriHashes[$routeName][KEY_JS]))
            $prodSriHashes[$routeName][KEY_JS] = $devSriHashes[$routeName][KEY_JS] = [];

          [$pathAndFile, $devSriHashes] =
            loadAndSaveResources($resources, $chunks, KEY_JS, $bundlePath, $shaName, $routeName, $devSriHashes);

          if ($pathAndFile === null)
          {
            echo CLI_ERROR, 'You are trying to generate empty assets for ', CLI_INFO_HIGHLIGHT, $routeName, CLI_ERROR,
              '!' . END_COLOR . PHP_EOL;
            continue;
          }

          $jsFileOut = mb_substr($pathAndFile, 0, -1);
          googleClosureCompile(
            0,
            json_decode(
              file_get_contents(BASE_PATH . OTRA_LABEL_TSCONFIG_JSON),
              true,
              512,
              JSON_THROW_ON_ERROR
            ),
            $pathAndFile,
            $jsFileOut,
            JS_LEVEL_COMPILATION
          );

          $prodSriFilePath = (str_contains($jsFileOut, 'https::'))
            ? $jsFileOut
            : 'l:' . substr($jsFileOut, strlen(BASE_PATH));

          $prodSriHashes[$routeName][KEY_JS][$prodSriFilePath . '.br'] = getSRIHash($jsFileOut);
          brotliCompressFile($jsFileOut, $jsFileOut . '.br', COMPRESSION_LEVEL);
          echo status('JS');
        }
        else
          echo status('NO JS', OTRA_CLI_INFO_STRING);
      }

      /***** TEMPLATE - GENERATES THE COMPRESSED TEMPLATE FILES IF THE ROUTE IS STATIC *****/
      if (GEN_ASSETS_TEMPLATE !== 0)
      {
        if (!isset($resources['template']) || !$resources['template'])
          echo status('NO TEMPLATE', OTRA_CLI_INFO_STRING); // no static template
        else
        {
          $staticTemplateBasePath = (isset($resources['noNonces']) && $resources['noNonces'])
            ? BASE_PATH . 'web/'
            : CACHE_PATH;

          // Generates the compressed template files
          passthru(PHP_BINARY . ' "' . CONSOLE_PATH . 'deployment/genAssets/genTemplate.php" "' .
            $staticTemplateBasePath . '" "' .
            $routeName . '" ' .
            $shaName . ' "' .
            'bundles\\' . Routes::$allRoutes[$routeName]['chunks'][Routes::ROUTES_CHUNKS_BUNDLE] . '\\Init::Init"'
          );

          if (file_exists(CACHE_PATH . 'tpl/' . $shaName . '.br'))
            echo status('TEMPLATE');
          else
          {
            echo status('TEMPLATE', 'CLI_ERROR');
            $noErrors = false;
          }
        }
      }

      echo ' => ', $noErrors ? CLI_SUCCESS . 'OK' . END_COLOR : CLI_ERROR . 'ERROR' . END_COLOR, '[',
      CLI_INFO, $shaName, END_COLOR, ']', PHP_EOL;

      // Avoid routes that do not have SRI hashes
      if (isset($prodSriHashes[$routeName]) && $prodSriHashes[$routeName] === [])
        unset($prodSriHashes[$routeName], $devSriHashes[$routeName]);
    }

    require CONSOLE_PATH . 'tools.php';

    saveSRIHashes(SRI_DEV_FULL_PATH, 'DEV_SRI', $devSriHashes);
    saveSRIHashes(SRI_PROD_FULL_PATH, 'PROD_SRI', $prodSriHashes);
  }

  if (GEN_ASSETS_MANIFEST !== 0)
  {
    $jsonManifestPath = BASE_PATH . 'web/devManifest.json';

    if (!file_exists($jsonManifestPath))
    {
      require_once CORE_PATH . 'tools/files/returnLegiblePath.php';
      echo CLI_ERROR, 'The JSON manifest file ', returnLegiblePath($jsonManifestPath, ''), CLI_ERROR, ' to optimize does not exist.',
      END_COLOR, PHP_EOL;
    }
    else
    {
      $message = 'Generation of the compressed JSON manifest';
      echo $message . '...', PHP_EOL;

      $contents = file_get_contents($jsonManifestPath);
      $contents = preg_replace(
        [
          '@([{,:])\s+(")@',
          '@([\[,])\s+(\{)@',
          '@(")\s+(\})@',
          '@(\})\s+(\])@'
        ],
        [
          '$1$2',
          '$1$2',
          '$1$2',
          '$1$2'
        ],
        $contents
      );

      $generatedJsonManifestPath = BASE_PATH . 'web/manifest';
      file_put_contents($generatedJsonManifestPath, $contents);
      brotliCompressFile($generatedJsonManifestPath, $generatedJsonManifestPath . '.br', COMPRESSION_LEVEL);
      echo "\033[1A\033[" . strlen($message) . "C", CLI_SUCCESS . '  ✔  ' . END_COLOR . PHP_EOL;
    }
  }

  if (GEN_ASSETS_SVG !== 0)
  {
    define(__NAMESPACE__ . '\\FOLDER_TO_CHECK_FOR_SVGS', BASE_PATH . 'web/images');
    echo 'Checking for uncompressed SVGs in the folder ', CLI_INFO_HIGHLIGHT, FOLDER_TO_CHECK_FOR_SVGS, END_COLOR, ' ...',
    PHP_EOL;

    // Searches in the 'web/images' folder for SVGs
    if (file_exists(FOLDER_TO_CHECK_FOR_SVGS))
    {
      $dirIterator = new RecursiveDirectoryIterator(FOLDER_TO_CHECK_FOR_SVGS, FilesystemIterator::SKIP_DOTS);
      $iterator = new RecursiveIteratorIterator($dirIterator);

      /** @var SplFileInfo $entry */
      foreach ($iterator as $entry)
      {
        $extension = $entry->getExtension();

        if ($extension !== 'svg' || $entry->isDir())
          continue;

        $realPath = $entry->getRealPath();

        if (!brotliCompressFile($realPath, $realPath . '.br', COMPRESSION_LEVEL))
        {
          echo CLI_ERROR, 'There was an error during the Brotli compression of the file ', CLI_INFO_HIGHLIGHT,
          mb_substr($realPath, strlen(BASE_PATH)), '.', END_COLOR, PHP_EOL;
        } else
        {
          echo 'The file ', CLI_INFO_HIGHLIGHT, mb_substr($realPath, strlen(BASE_PATH)), END_COLOR,
          ' has been compressed successfully.', END_COLOR, PHP_EOL;
        }
      }

      echo 'All SVGs are compressed.', PHP_EOL;
    }
    else
      echo CLI_WARNING, 'There is no folder ', CLI_INFO_HIGHLIGHT, FOLDER_TO_CHECK_FOR_SVGS, CLI_WARNING, '.', END_COLOR,
      PHP_EOL;
  }
}
