<?php
declare(strict_types=1);
namespace otra;

use config\{AllConfig, Routes};
use Exception;

define('ROUTE_CHUNKS_BUNDLE_PARAM', 1);
define('ROUTE_CHUNKS_MODULE_PARAM', 2);
define('OTRA_FILENAME_TRACE', 'trace');

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
   * @param array $baseParams
   * @param array $getParams
   */
  public function __construct(array $baseParams = [], array $getParams = [])
  {
    parent::__construct($baseParams, $getParams);

    if (!isset(AllConfig::$debugConfig['autoLaunch']) || AllConfig::$debugConfig['autoLaunch'])
      require CORE_PATH . 'tools/debug/dump.php';

    Logger::logTo(PHP_EOL . "\tRoute [" . $this->route . "] Patt : " . $this->pattern, OTRA_FILENAME_TRACE);
  }

  /**
   * Renders a view. NB: Even if the cache is activated, the template can be not fresh !
   *
   * @param string      $file      The file to render
   * @param array       $variables Variables to pass
   * @param bool        $ajax      Is this an ajax partial ?
   * @param bool|string $viewPath  If true, we adds the usual view path before the $file variable.
   *
   * @throws OtraException
   * @throws Exception
   * @return string parent::$template Content of the template
   *
   */
  final public function renderView(
    string $file,
    array $variables = [],
    bool $ajax = false,
    bool $viewPath = true) : string
  {
    [$templateFile, $otraRoute] = $this->getTemplateFile($file, $viewPath);

    // We log : ajax state, action variables and the template file name into logs/trace.txt
    Logger::logTo("\tAjax : " . ($ajax ? 'true' : 'false') . PHP_EOL .
      "\tVariables :" . PHP_EOL .
      print_r($variables, true) . PHP_EOL .
      'File : ' . $templateFile,
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
    addFeaturePoliciesHeader($this->route, $this->routeSecurityFilePath);

    return parent::$template;
  }

  /**
   * @param string $route
   * @param array  $viewResourcePath Paths to CSS and JS files
   *
   * @throws Exception
   * @return array
   */
  public static function getTemplateResources(string $route, array $viewResourcePath = []) : array
  {
    return [
      self::addResources('css', $route, $viewResourcePath),
      self::addResources('js', $route, $viewResourcePath)
    ];
  }

  /**
   * Adds a debug bar at the top of the template
   *
   * @throws Exception
   */
  private function addDebugBar() : void
  {
    ob_start();
    // send variables to the debug toolbar
    require CORE_VIEWS_PATH . '/debugBar/debugBar.phtml';
    parent::$template = (!str_contains(parent::$template, 'body'))
      ? ob_get_clean() . parent::$template
      : preg_replace(
        '`(<body[^>]*>)`',
        '$1' . ob_get_clean(),
        parent::$template
      );

    // suppress useless spaces
    parent::$template = str_replace(
      MasterController::OTRA_LABEL_ENDING_TITLE_TAG,
      MasterController::OTRA_LABEL_ENDING_TITLE_TAG . self::addDebugCSS(),
      str_replace(
        '</body>',
        self::addDebugJS() . '</body>',
        parent::$template
      )
    );
  }

  /**
   * Adds resources file to the template. Can be 'css' or 'js' resources.
   *
   * @param string $assetType        'css' or 'js'
   * @param string $route
   * @param array  $viewResourcePath
   *
   * @throws Exception
   * @return string
   */
  private static function addResources(string $assetType, string $route, array $viewResourcePath) : string
  {
    $routes = Routes::$_;

    // The route does not exist ?!
    if (!isset($routes[$route]))
      return '';

    $route = $routes[$route];

    // No resources section so no CSS/JS to load
    if (!isset($route['resources']))
      return '';

    $resourceContent = '';
    $chunks = $route['chunks'];

    // Bundle and module informations do not exist on exceptions
    if (!isset($chunks[ROUTE_CHUNKS_BUNDLE_PARAM]))
      $chunks[ROUTE_CHUNKS_BUNDLE_PARAM] = $chunks[ROUTE_CHUNKS_MODULE_PARAM] = '';

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

    $resourcesType = [
      'bundle_' . $assetType => $debLink2 . $chunks[ROUTE_CHUNKS_BUNDLE_PARAM] . '/resources/' . $assetType . '/',
      'module_' . $assetType => $debLink2 . $chunks[ROUTE_CHUNKS_MODULE_PARAM] . '/resources/' . $assetType . '/',
      '_' . $assetType => $debLink . $viewResourcePath[$assetType],
      'core_' . $assetType => $debLink . '/src/resources/' . $assetType . '/'
    ];

    // For each kind of JS file, we will looks for them in their respective folders
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
            $resourceTypeInfo);

          // Fills $orderedArray and/or $unorderedArray
          self::updateScriptsArray(
            $unorderedArray,
            $orderedArray,
            $naturalPriorityIndex,
            $forcedPriorityIndex,
            ($resourceTypeInfoActual ?? $resourceTypeInfo) . $resourceFile . $endLink
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
      foreach(self::$javaScript as $key => $javaScript)
      {
        // If the key don't give info on async and defer then put them automatically
        if (is_int($key))
          $key = '';

        $resourceContent .= PHP_EOL . '<script src="' . $javaScript . '.js" nonce="' . getRandomNonceForCSP() . '" ' .
          $key . '></script>';
      }
    }

    return $resourceContent;
  }

  /**
   * Adds the OTRA CSS for the debug bar.
   *
   * @return string
   */
  public static function addDebugCSS() : string
  {
    $cssContent = '';

    foreach(self::$stylesheets as $stylesheet)
    {
      $cssContent .= "\n" . '<link rel="stylesheet" href="' . $stylesheet . '.css" />';
    }

    return $cssContent;
  }

  /**
   * Adds the OTRA CSS for the debug bar.
   *
   * @throws Exception
   * @return string
   */
  public static function addDebugJS() : string
  {
    $jsContent = '';

    foreach(self::$javaScript as $javaScript)
    {
      $jsContent .= PHP_EOL . parent::LABEL_SCRIPT_NONCE .
      getRandomNonceForCSP() . '" src="' . $javaScript . '.js" ></script>';
    }

    return $jsContent;
  }

  /**
   * Uses calculations in order to put scripts in correct order that has been specified in the routes configuration file
   *
   * @param array $unorderedArray Unordered array of files
   * @param array $orderedArray   Ordered array of files
   *
   * @return array $scripts Final array
   */
  private static function calculateArray(array $unorderedArray, array $orderedArray) : array
  {
    $scripts = [];

    for($i = 0, $maximum = count($unorderedArray) + count($orderedArray);
        $i< $maximum;
        ++$i )
    {
      if (in_array($i, array_keys($orderedArray)))
      {
        $scripts[$i] = $orderedArray[$i];
        unset($orderedArray[$i]);
      } else
        $scripts[$i] = array_shift($unorderedArray);
    }

    return $scripts;
  }

  /**
   * Updates the CSS or JS scripts array in order to allow scripts generation order calculations.
   * If we put things like '_js' => ['_5'=>'users']
   * then the $key will be 5 and not the key that follows natural order.
   *
   * @param array      &$unorderedArray
   * @param array      &$orderedArray
   * @param int        &$naturalPriorityIndex Used if $forcedPriorityIndex is not a string
   * @param int|string  $forcedPriorityIndex  Used only if it is a string
   * @param string      $code
   */
  private static function updateScriptsArray(
    array &$unorderedArray,
    array &$orderedArray,
    int &$naturalPriorityIndex,
    int|string $forcedPriorityIndex,
    string $code)
  {
    // A 'substr' is done to remove the '_' before the priority index
    if (is_string($forcedPriorityIndex))
      $orderedArray[intval(substr($forcedPriorityIndex,1))] = $code;
    else
      $unorderedArray[$naturalPriorityIndex] = $code;

    ++$naturalPriorityIndex;
  }
}
