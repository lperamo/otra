<?
/**
 * A classic MVC development controller class
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace lib\myLibs;

use config\{AllConfig, Routes};
use lib\myLibs\{Logger, MasterController};

class Controller extends MasterController
{
  const ROUTE_CHUNKS_MODULE_PARAM = 1;
  const ROUTE_CHUNKS_BUNDLE_PARAM = 1;

  public function __construct(array $baseParams = [], array $getParams = [])
  {
    parent::__construct($baseParams, $getParams);
    Logger::logTo(PHP_EOL . "\tRoute [" . $this->route . "] Patt : " . $this->pattern, 'trace');
  }

  /**
   * If the files are in cache, put them directly in $rendered
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
   * @param bool|string $viewPath  Using the view path or not
   *
   * @return string parent::$template Content of the template
   *
   * @throws OtraException
   */
  public final function renderView(string $file, array $variables = [], bool $ajax = false, bool $viewPath = true) : string
  {
    $templateFile = ($viewPath) ? $this->viewPath . $file : $file;
    Logger::logTo("\t" . 'Ajax : ' . ((true === $ajax) ? 'true' : 'false'), 'trace');

    if (false === file_exists($templateFile))
      throw new OtraException('File not found ! : ' . $templateFile);

    if (true === $ajax)
      self::$ajax = $ajax;

    // we use self::ajax in this function (it is why we cannot merge the two if with self::$ajax
    parent::$template = $this->buildCachedFile($templateFile, $variables);

    if (false === $ajax
      && (
        property_exists(AllConfig::class, 'debug') === false
        || property_exists(AllConfig::class, 'debug') === true && AllConfig::$debug !== false
      )
    )
      self::addDebugBar();

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
    Logger::logTo(print_r($variables, true), 'trace');
    extract($variables);

    ob_start();
    require $templateFilename;
    self::$currentBlock['content'] .= ob_get_clean();
    array_push(self::$blocksStack, self::$currentBlock);
    $content = '';
    $indexesToUnset = [];

    // Loops through the block stack to compile the final content that have to be shown
    foreach(self::$blocksStack as $key => &$block)
    {
      $blockExists = array_key_exists($block['name'], MasterController::$blockNames);

      // If there are other blocks with this name...
      if ($blockExists === true)
      {
        $goodBlock = &$block;

        // We seeks for the last block with this name and we adds its content
        while(array_key_exists('replacedBy', $goodBlock) === true)
        {
          $goodBlock['content'] = '';
          $indexesToUnset[$goodBlock['index']] = true;
          $tmpKey = $key;
          $tmpBlock = &MasterController::$blocksStack[$tmpKey + 1];

          while ($tmpBlock['parent'] === MasterController::$blocksStack[$tmpKey] && $tmpBlock['name'] !== $block['name'])
          {
            $tmpBlock['content'] = '';
            $indexesToUnset[$tmpBlock['index']] = true;
            $tmpBlock = &MasterController::$blocksStack[++$tmpKey + 1];
          }

          $goodBlock = &MasterController::$blocksStack[$goodBlock['replacedBy']];
        }

        // We must also not show the endings blocks that have been replaced
        if (in_array($goodBlock['index'], array_keys($indexesToUnset)) === false)
          $content .= $goodBlock['content'];

        $goodBlock['content'] = '';
      } else
        $content .= $block['content'];
    }

    // We log the template file name into logs/trace.txt
    Logger::logTo("\t" . 'File : ' . $templateFilename, 'trace');

    // /!\ We have to put these functions in this order to put the css before ! (in order to optimize the loading)
    $content = false === self::$ajax
      ? str_replace(
        '/title>',
        '/title>'. self::addCss(),
        $content . self::addJs())
      : self::addCss() . $content . self::addJs();

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
    require CORE_VIEWS_PATH . '/debugBar.phtml';
    parent::$template = (false === strpos(parent::$template, 'body'))
                        ? ob_get_clean() . parent::$template
                        : preg_replace('`(<body[^>]*>)`', '$1' . ob_get_clean(), parent::$template);

    // suppress useless spaces
    parent::$template = str_replace(
      '/title>',
      '/title>'. self::addDebugCSS(),
      parent::$template . self::addJs()
    );
  }

  /**
   * Puts the css into the template
   *
   * @return string The links to the css files or the style markup with the css inside
   */
  private function addCss() : string
  {
    $route = Routes::$_;

    // The route does not exist ?!
    if (false === array_key_exists($this->route, $route))
      return '';

    $route = $route[$this->route];

    // No resources section so no CSS to load
    if (false === array_key_exists('resources', $route))
      return '';

    $cssContent = '';
    $chunks = $route['chunks'];

    // Bundle and module informations do not exist on exceptions
    if (array_key_exists(self::ROUTE_CHUNKS_BUNDLE_PARAM, $chunks) === false)
      $chunks[self::ROUTE_CHUNKS_BUNDLE_PARAM] = $chunks[self::ROUTE_CHUNKS_MODULE_PARAM] = '';

    $resources = $route['resources'];
    $debLink = "\n" . '<link rel="stylesheet" href="';
    $i = 0;
    $unorderedArray = $orderedArray = [];
    $debLink2 = $debLink . '/bundles/';


    $resourcesType = [
      'bundle_css' => $debLink2 . $chunks[self::ROUTE_CHUNKS_BUNDLE_PARAM] . '/resources/css/',
      'module_css' => $debLink2 . $chunks[self::ROUTE_CHUNKS_MODULE_PARAM] . '/resources/css/',
      '_css' => $debLink . $this->viewCSSPath,
      'core_css' => $debLink . '/lib/myLibs/resources/css/'
    ];

    // For each kind of CSS file, we will looks for them in their respective folders
    foreach ($resourcesType as $resourceType => &$resourceTypeInfo)
    {
      if (true === array_key_exists($resourceType, $resources))
      {
        // We add a link to the CSS array for each CSS we found
        foreach ($resources[$resourceType] as $key => &$cssFile)
        {
          // Fills $orderedArray and/or $unorderedArray
          self::updateScriptsArray(
            $unorderedArray,
            $orderedArray,
            $i,
            $key,
            $resourceTypeInfo . $cssFile . '.css" />'
          );
        }
      }
    }

    // Merges the two arrays into one final array respecting order of files
    $cssArray = self::calculateArray($unorderedArray, $orderedArray);

    foreach ($cssArray as &$css)
    {
      $cssContent .= $css;
    }

    return $cssContent;
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

  /**
   * Puts the js into the template. Updates parent::$template.
   *
   * @return string The links to the js files or the script markup with the js inside
   */
  private function addJs() : string
  {
    $route = Routes::$_;

    // The route does not exist ?!
    if (false === array_key_exists($this->route, $route))
      return '';

    $route = $route[$this->route];

    // No resources section so no JS to load
    if (false === array_key_exists('resources', $route))
      return '';

    $jsContent = '';

    $chunks = $route['chunks'];

    // Bundle and module informations do not exist on exceptions
    if (array_key_exists(self::ROUTE_CHUNKS_BUNDLE_PARAM, $chunks) === false)
      $chunks[self::ROUTE_CHUNKS_BUNDLE_PARAM] = $chunks[self::ROUTE_CHUNKS_MODULE_PARAM] = '';

    $resources = $route['resources'];
    $debLink = "\n" . '<script type="application/javascript" src="';
    $i = 0;
    $unorderedArray = $orderedArray = [];
    $debLink2 = $debLink . '/bundles/';

    $resourcesType = [
      'bundle_js' => $debLink2 . $chunks[self::ROUTE_CHUNKS_BUNDLE_PARAM] . '/resources/js/',
      'module_js' => $debLink2 . $chunks[self::ROUTE_CHUNKS_MODULE_PARAM] . '/resources/js/',
      '_js' => $debLink . $this->viewJSPath,
      'core_js' => $debLink . '/lib/myLibs/resources/js/'
    ];

    // For each kind of JS file, we will looks for them in their respective folders
    foreach ($resourcesType as $resourceType => &$resourceTypeInfo)
    {
      if (true === array_key_exists($resourceType, $resources))
      {
        // We add a link to the CSS array for each CSS we found
        foreach($resources[$resourceType] as $key => &$cssFile)
        {
          self::updateScriptsArray(
            $unorderedArray,
            $orderedArray,
            $i,
            $key,
            $resourceTypeInfo . $cssFile . '.js" ></script>'
          );
        }
      }
    }

    $jsArray = self::calculateArray($unorderedArray, $orderedArray);

    foreach($jsArray as &$js) { $jsContent .= $js; }

    foreach(self::$js as $key => &$js)
    {
      // If the key don't give info on async and defer then put them automatically
      if (true === is_int($key))
        $key = '';

      $jsContent .= "\n" . '<script src="' . $js . '.js" ' . $key . '></script>';
    }

    return $jsContent;
  }
}
?>
