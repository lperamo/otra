<?php
/**
 * A classic MVC development controller class
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace otra;

use config\{AllConfig, Routes};

define('ROUTE_CHUNKS_BUNDLE_PARAM', 1);
define('ROUTE_CHUNKS_MODULE_PARAM', 2);

define('OTRA_LABEL_ENDING_TITLE_TAG', '/title>');
define('OTRA_FILENAME_TRACE', 'trace');

/**
 * @package otra
 */
trait DevControllerTrait
{
  /**
   * @param array $baseParams
   * @param array $getParams
   */
  public function __construct(array $baseParams = [], array $getParams = [])
  {
    parent::__construct($baseParams, $getParams);
    Logger::logTo(PHP_EOL . "\tRoute [" . $this->route . "] Patt : " . $this->pattern, OTRA_FILENAME_TRACE);
  }

  /**
   * If the files are in cache, put them directly in $rendered
   * @TODO Fix this function !!
   *
   * @param array $filesToCheck Files to check in cache
   *
   * @return bool True if ALL the files are in cache, false otherwise
   */
  public function checkCache(array $filesToCheck) : bool { return false; }

  /**
   * Renders a view. NB: Even if the cache is activated, the template can be not fresh !
   *
   * @param string      $file      The file to render
   * @param array       $variables Variables to pass
   * @param bool        $ajax      Is this an ajax partial ?
   * @param bool|string $viewPath  If true, we adds the usual view path before the $file variable.
   *
   * @return string parent::$template Content of the template
   *
   * @throws OtraException
   */
  final public function renderView(string $file, array $variables = [], bool $ajax = false, bool $viewPath = true) : string
  {
    $otraRoute = strpos($this->route, 'otra_') !== false;

    if ($otraRoute === false)
      $templateFile = ($viewPath === true) ? $this->viewPath . $file : $file;
    else
      $templateFile = CORE_VIEWS_PATH . $this->controller . '/' . $file;

    Logger::logTo("\t" . 'Ajax : ' . ((true === $ajax) ? 'true' : 'false'), OTRA_FILENAME_TRACE);

    if (false === file_exists($templateFile))
      throw new OtraException('File not found ! : ' . $templateFile);

    if (true === $ajax)
      self::$ajax = $ajax;

    // we use self::ajax in this function (it is why we cannot merge the two if with self::$ajax
    parent::$template = $this->buildCachedFile($templateFile, $variables);

    // If it is not an ajax route, debug is active (or not defined) and it is not an internal route, we show the debug bar
    if (false === $ajax
      && (
        property_exists(AllConfig::class, 'debug') === false
        || property_exists(AllConfig::class, 'debug') === true && AllConfig::$debug !== false
      )
      && $otraRoute === false
    )
      self::addDebugBar();

    $this->addCspHeader();
    $this->addFeaturePoliciesHeader();

    return parent::$template;
  }

  /**
   * Parses the template file and updates parent::$template
   *
   * @param string  $templateFilename The file name
   * @param array   $variables        Variables to pass to the template
   * @param string  $cachedFile       The cache file name version of the file (Unused in dev mode... TODO WE MUST FIX IT !
   *
   * @return mixed|string
   */
  private function buildCachedFile(string $templateFilename, array $variables, string $cachedFile = null) : string
  {
    // We log the action variables into logs/trace.txt
    Logger::logTo(print_r($variables, true), OTRA_FILENAME_TRACE);

    $content = MasterController::processFinalTemplate($templateFilename, $variables);

    // We log the template file name into logs/trace.txt
    Logger::logTo("\t" . 'File : ' . $templateFilename, OTRA_FILENAME_TRACE);

    // /!\ We have to put these functions in this order to put the css before ! (in order to optimize the loading)
    $content = false === self::$ajax
      ? str_replace(
        OTRA_LABEL_ENDING_TITLE_TAG,
        OTRA_LABEL_ENDING_TITLE_TAG. self::addResource('css'),
        $content . self::addResource('js'))
      : self::addResource('css') . $content . self::addResource('js');

    // We clear these variables in order to put css and js for other modules that will not be cached (in case there are css and js imported in the layout)
    self::$js = self::$css = [];

    return $content;
  }

  /**
   * Adds a debug bar at the top of the template
   */
  private function addDebugBar()
  {
    ob_start();
    // send variables to the debug toolbar (if debug is active, cache don't)
    require CORE_VIEWS_PATH . '/profiler/debugBar.phtml';
    parent::$template = (false === strpos(parent::$template, 'body'))
      ? ob_get_clean() . parent::$template
      : preg_replace('`(<body[^>]*>)`', '$1' . ob_get_clean(), parent::$template);

    // suppress useless spaces
    parent::$template = str_replace(
      OTRA_LABEL_ENDING_TITLE_TAG,
      OTRA_LABEL_ENDING_TITLE_TAG. self::addDebugCSS(),
      parent::$template . self::addDebugJS()
    );
  }

  /**
   * Adds resources file to the template. Can be 'css' or 'js' resources.
   *
   * @param string $assetType 'css' or 'js'
   *
   * @return string
   */
  private function addResource(string $assetType) : string
  {
    $route = Routes::$_;

    // The route does not exist ?!
    if (false === array_key_exists($this->route, $route))
      return '';

    $route = $route[$this->route];

    // No resources section so no CSS/JS to load
    if (false === array_key_exists('resources', $route))
      return '';

    $resourceContent = '';

    $chunks = $route['chunks'];

    // Bundle and module informations do not exist on exceptions
    if (array_key_exists(ROUTE_CHUNKS_BUNDLE_PARAM, $chunks) === false)
      $chunks[ROUTE_CHUNKS_BUNDLE_PARAM] = $chunks[ROUTE_CHUNKS_MODULE_PARAM] = '';

    $resources = $route['resources'];
    $debLink = "\n" . ($assetType === 'js'
        ? '<script nonce="' . parent::getRandomNonceForCSP() . '" src="'
        : '<link rel="stylesheet" href="'
      );

    $endLink = ($assetType === 'js')
      ? '.js" ></script>'
      : '.css" />';

    $i = 0;
    $unorderedArray = $orderedArray = [];
    $debLink2 = $debLink . '/bundles/';

    $resourcesType = [
      'bundle_' . $assetType => $debLink2 . $chunks[ROUTE_CHUNKS_BUNDLE_PARAM] . '/resources/' . $assetType . '/',
      'module_' . $assetType => $debLink2 . $chunks[ROUTE_CHUNKS_MODULE_PARAM] . '/resources/' . $assetType . '/',
      '_' . $assetType => $debLink . $this->viewResourcePath[$assetType],
      'core_' . $assetType => $debLink . '/src/resources/' . $assetType . '/'
    ];

    // For each kind of JS file, we will looks for them in their respective folders
    foreach ($resourcesType as $resourceType => &$resourceTypeInfo)
    {
      if (true === array_key_exists($resourceType, $resources))
      {
        // We add a link to the CSS/JS array for each file we found
        foreach($resources[$resourceType] as $key => &$file)
        {
          // Fills $orderedArray and/or $unorderedArray
          self::updateScriptsArray(
            $unorderedArray,
            $orderedArray,
            $i,
            $key,
            $resourceTypeInfo . $file . $endLink
          );
        }
      }
    }

    $resourcesArray = self::calculateArray($unorderedArray, $orderedArray);

    foreach ($resourcesArray as &$file)
    {
      $resourceContent .= $file;
    }

    if ($assetType === 'js')
    {
      foreach(self::$js as $key => &$js)
      {
        // If the key don't give info on async and defer then put them automatically
        if (true === is_int($key))
          $key = '';

        $resourceContent .= "\n" . '<script src="' . $js . '.js" nonce="' . parent::getRandomNonceForCSP() . '" ' . $key . '></script>';
      }
    }

    return $resourceContent;
  }

  /**
   * Adds the OTRA CSS for the debug bar.
   */
  public static function addDebugCSS()
  {
    $cssContent = '';

    foreach(self::$css as &$css) { $cssContent .= "\n" . '<link rel="stylesheet" href="' . $css . '.css" />'; }

    return $cssContent;
  }

  /**
   * Adds the OTRA CSS for the debug bar.
   */
  public static function addDebugJS()
  {
    $jsContent = '';

    foreach(self::$js as &$js)
    {
      $jsContent .= "\n" . '<script nonce="' .
      parent::getRandomNonceForCSP() . '" src="' . $js . '.js" ></script>';
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

    for($i = 0, $max = count($unorderedArray) + count($orderedArray); $i< $max; ++$i )
    {
      if (true === in_array($i, array_keys($orderedArray)))
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
   *
   * @param array      &$unorderedArray
   * @param array      &$orderedArray
   * @param int        &$i
   * @param int|string $key
   * @param string     $code
   */
  private static function updateScriptsArray(array &$unorderedArray, array &$orderedArray, int &$i, $key, string $code)
  {
    if (true === is_string($key))
      $orderedArray[intval(substr($key,1))] = $code;
    else
      $unorderedArray[$i] = $code;

    ++$i;
  }
}

