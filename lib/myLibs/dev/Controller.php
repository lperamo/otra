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
   * @return string parent::$template Content of the template
   *
   * @throws LionelException
   */
  public final function renderView(string $file, array $variables = [], bool $ajax = false, bool $viewPath = true) : string
  {
    $templateFile = ($viewPath) ? $this->viewPath . $file : $file;
    Logger::logTo("\t" . 'Ajax : ' . ((true === $ajax) ? 'true' : 'false'), 'trace');

    if (false === file_exists($templateFile))
      throw new LionelException('File not found ! : ' . $templateFile);

    if (true === $ajax)
      self::$ajax = $ajax;

    // we use self::ajax in this function (it is why we cannot merge the two if with self::$ajax
    parent::$template = $this->buildCachedFile($templateFile, $variables);

    if (false === $ajax)
      self::addDebugBar();

    return parent::$template;
  }

  /**
   * Parses the template file and updates parent::$template
   *
   * @param string  $templateFilename The file name
   * @param array   $variables        Variables to pass to the template
   * @param string  $cachedFile       The cache file name version of the file (Unused in dev mode... TODO WE MUST FIX IT !
   * @param bool    $layout           If we add a layout or not
   *
   * @return mixed|string
   */
  private function buildCachedFile(string $templateFilename, array $variables, string $cachedFile = null, bool $layout = true) : string
  {
    // We log the action variables into logs/trace.txt
    Logger::logTo(print_r($variables, true), 'trace');
    extract($variables);

    ob_start();
    require $templateFilename;
    $content = (true === $layout && false === parent::$layoutOnce) ? parent::addLayout(ob_get_clean()) : ob_get_clean();

    // We log the template file name into logs/trace.txt
    Logger::logTo("\t" . 'File : ' . $templateFilename, 'trace');

    // /!\ We have to put these functions in this order to put the css before ! (in order to optimize the loading)
    $content = false === self::$ajax
      ? str_replace(
        '/title>',
        '/title>'. self::addCss($layout),
        $content . self::addJs($layout))
      : self::addCss($layout) . $content . self::addJs($layout);

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
      '/title>'. self::addCss(false),
      parent::$template . self::addJs(false)
    );
  }


  /** Includes the layout */
  private function layout() { parent::$layout = $this->buildCachedFile(LAYOUT, [], null, false); }

  /**
   * Puts the css into the template
   *
   * @param bool $firstTime If it's not the layout, often the first time we arrive at that function.
   *
   * @return string The links to the css files or the style markup with the css inside
   */
  private function addCss(bool $firstTime) : string
  {
    $route = Routes::$_;
    $debugContent = '';

    if (true === $firstTime && true === isset($route[$this->route]))
    {
      $route = $route[$this->route];

      if (true === isset($route['resources']))
      {
        $chunks = $route['chunks'];
        $resources = $route['resources'];
        $debLink = "\n" . '<link rel="stylesheet" href="';
        $i = 0;
        $unorderedArray = $orderedArray = [];
        $debLink2 = $debLink . '/bundles/';

        if (true === isset($resources['bundle_css']))
        {
          foreach($resources['bundle_css'] as $key => &$bundle_css)
          {
            self::updateScriptsArray(
              $unorderedArray,
              $orderedArray,
              $i,
              $key,
              $debLink2 . $chunks[1] . '/resources/css/' . $bundle_css . '.css" />'
            );
          }
        }

        if (true === isset($resources['module_css']))
        {
          foreach($resources['module_css'] as $key => &$module_css)
          {
            self::updateScriptsArray(
              $unorderedArray,
              $orderedArray,
              $i,
              $key,
              $debLink2 . $chunks[2] . '/resources/css/' . $module_css . '.css" />'
            );
          }
        }

        if (true === isset($resources['_css']))
        {
          foreach($resources['_css'] as $key => &$css)
          {
            self::updateScriptsArray(
              $unorderedArray,
              $orderedArray,
              $i,
              $key,
              $debLink . $this->viewCSSPath . $css . '.css" />'
            );
          }
        }

        if (true === isset($resources['core_css']))
        {
          foreach($resources['core_css'] as $key => &$core_css)
          {
            self::updateScriptsArray(
              $unorderedArray,
              $orderedArray,
              $i,
              $key,
              $debLink . '/lib/myLibs/resources/css/' . $core_css . '.css" />'
            );
          }
        }

        $cssArray = self::calculateArray($unorderedArray, $orderedArray);

        foreach($cssArray as &$css) { $debugContent .= $css; }
      }
    }

    if (true === empty(self::$css)) return $debugContent;

    foreach(self::$css as &$css) { $debugContent .= "\n" . '<link rel="stylesheet" href="' . $css . '.css" />'; }

    return $debugContent;
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
   * @param bool $firstTime If it's not the layout, often the first time we arrive at that function.
   *
   * @return The links to the js files or the script markup with the js inside
   */
  private function addJs(bool $firstTime) : string
  {
    $route = Routes::$_;
    $debugContent = '';

    if (true === $firstTime && true === isset($route[$this->route]))
    {
      $route = $route[$this->route];

      if (true === isset($route['resources']))
      {
        $chunks = $route['chunks'];
        $resources = $route['resources'];
        $debLink = "\n" . '<script type="application/javascript" src="';
        $i = 0;
        $unorderedArray = $orderedArray = [];
        $debLink2 = $debLink . '/bundles/';

        if (true === isset($resources['bundle_js']))
        {
          foreach($resources['bundle_js'] as $key => &$bundleJs)
          {
            self::updateScriptsArray(
              $unorderedArray,
              $orderedArray,
              $i,
              $key,
              $debLink2 . $chunks[1] . '/resources/js/' . $bundleJs . '.js" ></script>'
            );
          }
        }

        if(true === isset($resources['module_js']))
        {
          foreach($resources['module_js'] as $key => &$module_js)
          {
            self::updateScriptsArray(
              $unorderedArray,
              $orderedArray,
              $i,
              $key,
              $debLink2 . $chunks[2] . '/resources/js/' . $module_js . '.js" ></script>'
            );
          }
        }

        if(true === isset($resources['_js']))
        {
          foreach($resources['_js'] as $key => &$js)
          {
            self::updateScriptsArray(
              $unorderedArray,
              $orderedArray,
              $i,
              $key,
              $debLink . $this->viewJSPath . $js . '.js" ></script>'
            );
          }
        }

        if(true === isset($resources['core_js']))
        {
          foreach($resources['core_js'] as $key => &$core_js)
          {
            self::updateScriptsArray(
              $unorderedArray,
              $orderedArray,
              $i,
              $key,
              $debLink . '/lib/myLibs/resources/js/' . $core_js . '.js" ></script>'
            );
          }
        }

        $jsArray = self::calculateArray($unorderedArray, $orderedArray);

        foreach($jsArray as &$js) { $debugContent .= $js; }
      }
    }

    foreach(self::$js as $key => &$js)
    {
      // If the key don't give info on async and defer then put them automatically
      if (true === is_int($key))
        $key = '';

      $debugContent .= "\n" . '<script src="' . $js . '.js" ' . $key . '></script>';
    }

    return $debugContent;
  }
}
?>
