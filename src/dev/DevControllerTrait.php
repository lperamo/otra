<?php
declare(strict_types=1);
namespace otra;

use otra\config\{AllConfig, Routes};
use Exception;
use otra\cache\php\Logger;
use otra\templating\HtmlMinifier;
use const otra\cache\php\
{BASE_PATH, CORE_CSS_PATH, CORE_JS_PATH, CORE_PATH, CORE_VIEWS_PATH, DEV_SRI, DIR_SEPARATOR};
use const otra\services\{OTRA_KEY_SCRIPT_SRC_DIRECTIVE, OTRA_KEY_STYLE_SRC_DIRECTIVE};
use function otra\tools\getOtraCommitNumber;
use function otra\services\{addCspHeader, addPermissionsPoliciesHeader, getRandomNonceForCSP};

const
  OTRA_FILENAME_TRACE = 'trace',
  RESOURCE_PATH_BUNDLE = '/bundles/';

/**
 * A classic MVC development controller class
 *
 * @author Lionel Péramo
 * @package otra\controllers
 */
trait DevControllerTrait
{
  private static bool $debugBarHasBeenAdded = false;

  /**
   * @param array $otraParams [pattern, bundle, module, controller, action, route, js, css, internalRedirect]
   * @param array $params     [...getParams, ...postParams, etc.]
   */
  public function __construct(array $otraParams = [], array $params = [])
  {
    parent::__construct($otraParams, $params);

    if (!isset(AllConfig::$debugConfig['autoLaunch']) || AllConfig::$debugConfig['autoLaunch'])
      require CORE_PATH . 'tools/debug/dump.php';

    Logger::logTo(
      json_encode(
        [
          'r' => $this->route,
          'p' => $this->pattern,
          'hrc' => http_response_code()
        ],
        Logger::LOG_JSON_MASK
      ),
      OTRA_FILENAME_TRACE
    );
  }

  /**
   * Renders a view. NB: Even if the cache is activated, the template can be not fresh !
   *
   * @param string      $file      The file to render
   * @param array       $variables Variables to pass
   * @param bool        $ajax      Is this an ajax partial ?
   * @param bool|string $viewPath  If true, we add the usual view path before the $file variable. Otherwise, there is NO
   *                               path before the `$file` variable allowing us to put an entirely customized absolute
   *                               path. Affects template files AND assets.
   *
   * @throws OtraException
   * @throws Exception
   * @return string parent::$template Content of the template
   */
  final public function renderView(
    string $file,
    array $variables = [],
    bool $ajax = false,
    bool|string $viewPath = true) : string
  {
    [$templateFile, $otraRoute] = $this->getTemplateFile($file, $viewPath);

    // We log : ajax state, action variables and the template file name into logs/trace.txt
    // a for AJAX, v for variables, f for file
    Logger::logTo(
      json_encode(
        [
          'a' => ($ajax ? '✔' : '✘'),
          'v' => $variables,
          'f' => str_replace([CORE_PATH, BASE_PATH], ['CORE_PATH + ', 'BASE_PATH + '], $templateFile)
        ],
        Logger::LOG_JSON_MASK
      ),
      OTRA_FILENAME_TRACE
    );

    if (!file_exists($templateFile))
      throw new OtraException('File not found ! : ' . $templateFile);

    // If the cache was not used then ...
    parent::handleCache($templateFile, $variables, $ajax, $this->route, $this->viewResourcePath);

    $debugConfigurationExists = property_exists(AllConfig::class, 'debug');

    // If it is not an ajax route, debug is active (or not defined) and it is not an internal route,
    // we show the debug bar
    if (!$ajax &&
      ($debugConfigurationExists && AllConfig::$debug || !$debugConfigurationExists)
      && !$otraRoute
      && !self::$debugBarHasBeenAdded
    )
    {
      self::addDebugBar();
      self::$debugBarHasBeenAdded = true;
    }

    addCspHeader($this->route, $this->routeSecurityFilePath);
    addPermissionsPoliciesHeader($this->route, $this->routeSecurityFilePath);

    return parent::$template;
  }

  /**
   * @param array{css: string, js:string} $viewResourcePath Paths to CSS and JS files
   *
   * @throws Exception
   * @return string[]
   */
  public static function getTemplateResources(string $route, array $viewResourcePath = []) : array
  {
    return [
      self::addResources('css', $route, $viewResourcePath),
      self::$ajax
        ? ''
        : self::addResources('js', $route, $viewResourcePath)
    ];
  }

  /**
   * Adds a debug bar at the top of the template
   *
   * @throws Exception
   */
  private function addDebugBar() : void
  {
    require CORE_PATH . 'tools/getOtraCommitNumber.php';
    $otraCommitNumber = getOtraCommitNumber();

    ob_start();
    // send variables to the debug toolbar
    require CORE_VIEWS_PATH . '/debugBar/debugBar.phtml';
    $minifiedDebugBar = HtmlMinifier::minifyHTML(ob_get_clean());

    parent::$template = (str_contains(parent::$template, 'body'))
      ? preg_replace(
        '`(<body[^>]*>)`',
        '$1' . $minifiedDebugBar,
        parent::$template
      )
      : $minifiedDebugBar . parent::$template;

    parent::$template = str_replace(
      MasterController::OTRA_LABEL_ENDING_TITLE_TAG,
      MasterController::OTRA_LABEL_ENDING_TITLE_TAG . self::addDynamicCSS(),
      str_replace(
        '</body>',
        self::addDynamicJS() . '</body>',
        parent::$template
      )
    );
  }

  /**
   * Adds resources file to the template. Can be 'css' or 'js' resources.
   *
   * @param string $assetType                               'css' or 'js'
   * @param array{css: string, js:string} $viewResourcePath
   *
   * @throws Exception
   * @return string
   */
  private static function addResources(string $assetType, string $route, array $viewResourcePath) : string
  {
    $routes = Routes::$allRoutes;

    // The route does not exist ?!
    if (!isset($routes[$route]))
      return '';

    $routeData = $routes[$route];

    // No 'resources' section so no CSS/JS to load
    if (!isset($routeData['resources']))
      return '';

    $resources = $routeData['resources'];

    if (
      !isset($resources['app_' . $assetType])
      && !isset($resources['bundle_' . $assetType], $resources['core_' . $assetType], $resources['module_' . $assetType])
      && !($assetType === 'css' && isset($resources['print_css']))
    )
      return '';

    $chunks = $routeData['chunks'];

    // Bundle and module information do not exist on exceptions
    if (!isset($chunks[Routes::ROUTES_CHUNKS_BUNDLE]))
      $chunks[Routes::ROUTES_CHUNKS_MODULE] = '';

    $bundleChunk = $chunks[Routes::ROUTES_CHUNKS_BUNDLE] ?? '';

    $debLink = PHP_EOL;

    if ($assetType === 'js')
    {
      $debLink .= '<script nonce="<<<TO_REPLACE>>>" src="';
      $endLink = '.js"';
      $endLink2 = '></script>';
    } else
    {
      $debLink .= '<link rel="stylesheet" nonce="<<<TO_REPLACE>>>" href="';
      $endLink = '.css" />';
      $endLink2 = '/>';
    }

    $naturalPriorityIndex = 0;
    $resourcesArray = [];

    // **Reminder** : $viewsResourcePath is like
    // RESOURCE_PATH_BUNDLE . $this->bundle . '/' . $this->module . '/resources/css/'
    $resourcesType = [
      'app_' . $assetType => $debLink . RESOURCE_PATH_BUNDLE . 'resources/' . $assetType . DIR_SEPARATOR,
      'bundle_' . $assetType => $debLink . RESOURCE_PATH_BUNDLE . $bundleChunk . '/resources/' . $assetType .
        DIR_SEPARATOR,
      'module_' . $assetType => $debLink . $viewResourcePath[$assetType],
      'print_' . $assetType => $debLink . $viewResourcePath[$assetType],
      'core_' . $assetType => $debLink . ($assetType === 'css' ? CORE_CSS_PATH : CORE_JS_PATH)
    ];

    // For each kind of asset file, we will look for them in their respective folders
    foreach ($resourcesType as $resourceType => $resourceTypeInfo)
    {
      if (!isset($resources[$resourceType]))
        continue;

      $currentResources = $resources[$resourceType];

      // We add a link to the CSS/JS array for each file we found
      foreach($currentResources as $resourceFile => $resourceFileData)
      {
        // Normalizes the data structure
        if (!is_array($resourceFileData))
        {
          $resourceFile = $resourceFileData;
          $resourceFileData = ['order' => $naturalPriorityIndex];
        }

        if ($resourceType === 'app_' . $assetType) 
          $diskPath = BASE_PATH . 'bundles/resources/' . $assetType . '/' . $resourceFile . '.' . $assetType;
        elseif ($resourceType === 'bundle_' . $assetType)
          $diskPath = BASE_PATH . 'bundles/' . $bundleChunk . '/resources/' . $assetType . '/' . $resourceFile . '.' .
            $assetType;
        elseif ($resourceType === 'module_' . $assetType || $resourceType === 'print_' . $assetType)
          $diskPath = str_replace('/bundles', BASE_PATH . 'bundles', $viewResourcePath[$assetType]) .
            $resourceFile . '.' . $assetType;
        elseif ($resourceType === 'core_' . $assetType)
          $diskPath = BASE_PATH . 'vendor/otra/otra/src/resources/' . $assetType . '/' . $resourceFile . '.' . 
            $assetType;

        $cacheKey = str_starts_with($diskPath, BASE_PATH)
          ? 'l:' . substr($diskPath, strlen(BASE_PATH))
          : $diskPath;

        // Stores the data in a resources array
        $resourcesArray[$resourceFileData['order'] ?? $naturalPriorityIndex] =
          str_replace(
            '<<<TO_REPLACE>>>',
            getRandomNonceForCSP(
              $assetType === 'css'
                ? OTRA_KEY_STYLE_SRC_DIRECTIVE
                : OTRA_KEY_SCRIPT_SRC_DIRECTIVE
            ),
            $resourceTypeInfo
          ) . $resourceFile .
          ($resourceType !== 'print_css'
            ? '.' . $assetType . '"' . (isset($resourceFileData['module']) ? ' type=module' : '') . ' integrity=sha384-' .
            DEV_SRI[$route][$assetType][$cacheKey] . ' ' . $endLink2
            : '.css" media=print integrity=sha384-' . DEV_SRI[$route][$assetType][$cacheKey] . ' />'
          );

        ++$naturalPriorityIndex;
      }
    }

    $resourceContent = implode('', $resourcesArray);

    if ($assetType === 'js')
    {
      // $jsResourceKey can be 'async', 'defer' or a numerical array index
      foreach(self::$javaScripts as $jsResourceKey => $javaScript)
      {
        // If the key don't give info on async and defer then put them automatically
        if (is_int($jsResourceKey))
          $jsResourceKey = '';

        $resourceContent .= PHP_EOL . '<script type=module src="' . $javaScript . '.js" nonce="' .
          getRandomNonceForCSP() . '" ' . $jsResourceKey . ' integrity=sha384-' . DEV_SRI[$route][$assetType][$diskPath] . ' ></script>';
      }
    }

    return $resourceContent;
  }
}
