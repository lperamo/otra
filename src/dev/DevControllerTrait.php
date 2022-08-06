<?php
declare(strict_types=1);
namespace otra;

use otra\config\{AllConfig, Routes};
use Exception;
use otra\cache\php\Logger;
use function otra\tools\getOtraCommitNumber;
use const otra\cache\php\
{BASE_PATH, CORE_CSS_PATH, CORE_JS_PATH, CORE_PATH, CORE_VIEWS_PATH, DIR_SEPARATOR};
use const otra\services\{OTRA_KEY_SCRIPT_SRC_DIRECTIVE, OTRA_KEY_STYLE_SRC_DIRECTIVE};
use function otra\services\{addCspHeader, addPermissionsPoliciesHeader, getRandomNonceForCSP};

const OTRA_FILENAME_TRACE = 'trace';

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
   * @param array $otraParams [pattern, bundle, module, controller, action, route, js, css]
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
          'p' => $this->pattern
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
    bool $viewPath = true) : string
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
    parent::$template = (str_contains(parent::$template, 'body'))
      ? preg_replace(
        '`(<body[^>]*>)`',
        '$1' . ob_get_clean(),
        parent::$template
      )
      : ob_get_clean() . parent::$template;

    // suppress useless spaces
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
   * @param string $assetType 'css' or 'js'
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

    $route = $routes[$route];

    // No 'resources' section so no CSS/JS to load
    if (!isset($route['resources']))
      return '';

    $resourceContent = '';
    $chunks = $route['chunks'];

    // Bundle and module information do not exist on exceptions
    if (!isset($chunks[Routes::ROUTES_CHUNKS_BUNDLE]))
      $chunks[Routes::ROUTES_CHUNKS_BUNDLE] = $chunks[Routes::ROUTES_CHUNKS_MODULE] = '';

    $resources = $route['resources'];
    $debLink = PHP_EOL . ($assetType === 'js'
        ? '<script nonce="<<<TO_REPLACE>>>" src="'
        : '<link rel="stylesheet" nonce="<<<TO_REPLACE>>>" href="'
      );

    $endLink = ($assetType === 'js')
      ? '.js" ></script>'
      : '.css" />';

    $naturalPriorityIndex = 0;
    $unorderedArray = $orderedArray = [];
    $debLink2 = $debLink . '/bundles/';

    // **Reminder** : $viewsResourcePath is like
    // '/bundles/' . $this->bundle . '/' . $this->module . '/resources/css/'
    $resourcesType = [
      'app_' . $assetType => $debLink2 . 'resources/' . $assetType . DIR_SEPARATOR,
      'bundle_' . $assetType => $debLink2 . $chunks[Routes::ROUTES_CHUNKS_BUNDLE] . '/resources/' . $assetType . DIR_SEPARATOR,
      'module_' . $assetType => $debLink . $viewResourcePath[$assetType],
      'print_' . $assetType => $debLink . $viewResourcePath[$assetType],
      'core_' . $assetType => $debLink . ($assetType === 'css' ? CORE_CSS_PATH : CORE_JS_PATH)
    ];

    // For each kind of asset file, we will look for them in their respective folders
    foreach ($resourcesType as $resourceType => $resourceTypeInfo)
    {
      if (isset($resources[$resourceType]))
      {
        // We add a link to the CSS/JS array for each file we found
        foreach($resources[$resourceType] as $forcedPriorityIndex => $resourceFile)
        {
          $resourceTypeInfoActual = str_replace(
            '<<<TO_REPLACE>>>',
            getRandomNonceForCSP(
              $assetType === 'css'
                ? OTRA_KEY_STYLE_SRC_DIRECTIVE
                : OTRA_KEY_SCRIPT_SRC_DIRECTIVE
            ),
            $resourceTypeInfo
          );

          // Fills $orderedArray and/or $unorderedArray
          self::updateScriptsArray(
            $unorderedArray,
            $orderedArray,
            $naturalPriorityIndex,
            $forcedPriorityIndex,
            ($resourceTypeInfoActual ?? $resourceTypeInfo) . $resourceFile .
            ($resourceType !== 'print_css' ? $endLink : '.css" media="print" />')
          );
        }
      }
    }

    $resourcesArray = self::calculateArray($unorderedArray, $orderedArray);

    foreach ($resourcesArray as $resourceHtml)
    {
      $resourceContent .= $resourceHtml;
    }

    if ($assetType === 'js')
    {
      // $jsResourceKey can be 'async', 'defer' or a numerical array index
      foreach(self::$javaScripts as $jsResourceKey => $javaScript)
      {
        // If the key don't give info on async and defer then put them automatically
        if (is_int($jsResourceKey))
          $jsResourceKey = '';

        $resourceContent .= PHP_EOL . '<script src="' . $javaScript . '.js" nonce="' . getRandomNonceForCSP() . '" ' .
          $jsResourceKey . '></script>';
      }
    }

    return $resourceContent;
  }

  /**
   * Uses calculations in order to put scripts in correct order that has been specified in the routes' configuration file
   *
   * @param string[] $unorderedArray Unordered array of files
   * @param string[] $orderedArray   Ordered array of files
   *
   * @return string[] $scripts Final array
   */
  private static function calculateArray(array $unorderedArray, array $orderedArray) : array
  {
    $scripts = [];

    for($priorityIndex = 0, $maximum = count($unorderedArray) + count($orderedArray);
        $priorityIndex< $maximum;
        ++$priorityIndex )
    {
      if (array_key_exists($priorityIndex, $orderedArray))
      {
        $scripts[$priorityIndex] = $orderedArray[$priorityIndex];
        unset($orderedArray[$priorityIndex]);
      } else
        $scripts[$priorityIndex] = array_shift($unorderedArray);
    }

    return $scripts;
  }

  /**
   * Updates the CSS or JS scripts array in order to allow scripts generation order calculations.
   * If we put things like '_js' => ['_5'=>'users']
   * then the $key will be 5 and not the key that follows natural order.
   *
   * @param string[]   &$unorderedArray
   * @param string[]   &$orderedArray
   * @param int        &$naturalPriorityIndex Used if $forcedPriorityIndex is not a string
   * @param int|string $forcedPriorityIndex  Used only if it is a string
   */
  private static function updateScriptsArray(
    array &$unorderedArray,
    array &$orderedArray,
    int &$naturalPriorityIndex,
    int|string $forcedPriorityIndex,
    string $code): void
  {
    // A 'substr' is done to remove the '_' before the priority index
    if (is_string($forcedPriorityIndex))
      $orderedArray[(int) substr($forcedPriorityIndex,1)] = $code;
    else
      $unorderedArray[$naturalPriorityIndex] = $code;

    ++$naturalPriorityIndex;
  }
}
