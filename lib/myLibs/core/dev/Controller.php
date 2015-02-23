<?
/** A classic MVC development controller class
 *
 * @author Lionel Péramo */
namespace lib\myLibs\core;

use config\All_Config,
    lib\myLibs\core\Logger,
    config\Routes,
    lib\myLibs\core\MasterController;

class Controller extends MasterController
{
  public $viewPath = '/'; // index/index/ for indexController and indexAction

  private static $css = [],
    $js = [],
    $rendered = [],
    $ajax = false;

  public function __construct(array $baseParams = [], array $getParams = []){
    parent::__construct($baseParams, $getParams);
    Logger::logTo(PHP_EOL . "\tRoute [" . $this->route . "] Patt : " . $this->pattern, 'trace');
  }

  /** If the files are in cache, put them directly in $rendered
   *
   * @param array $filesToCheck Files to check in cache
   *
   * @return bool True if ALL the files are in cache, false otherwise
   */
  public function checkCache(array $filesToCheck) { return false; }

  /** Renders a view. NB: Even is cache is activated, the template can be not fresh !
   *
   * @param string $file      The file to render
   * @param array  $variables Variables to pass
   * @param bool   $ajax      Is this an ajax partial ?
   * @param string $viewPath  Using the view path or not
   *
   * @return string parent::$template Content of the template
   */
  public final function renderView($file, array $variables = [], $ajax = false, $viewPath = true)
  {
    $templateFile = ($viewPath) ? $this->viewPath . $file : $file;
    Logger::logTo("\t" . 'Ajax : ' . (($ajax) ? 'true' : 'false'), 'trace');

    if($ajax)
      $this->ajax = $ajax;

    if (file_exists($templateFile))
      parent::$template = $this->buildCachedFile($templateFile, $variables);
    else
      throw new Lionel_Exception('Erreur : Fichier non trouvé ! : ' , $templateFile);

    if(!$ajax)
      self::addDebugBar(CORE_VIEWS_PATH . DS . 'debugBar.phtml');

    return parent::$template;
  }

  /** Parses the template file and updates parent::$template
   *
   * @param string $filename  The file name
   * @param array  $variables Variables to pass to the template
   * @param sting  $cacheFile The cache file name version of the file
   * @param bool   $layout    If we add a layout or not
   */
  private function buildCachedFile($filename, array $variables, $cachedFile = null, $layout = true)
  {
    extract($variables);

    ob_start();
    require $filename;
    $content = ($layout && !parent::$layoutOnce) ? parent::addLayout(ob_get_clean()) : ob_get_clean();

    Logger::logTo("\t" . 'File : ' . $filename, 'trace');

    // /!\ We have to put these functions in this order to put the css before ! (in order to optimize the loading)
    $content = !$this->ajax
      ? str_replace(
        '/title>',
        '/title>'. self::addCss($layout),
        $content . self::addJs($layout))
      : $content . self::addCss($layout) . self::addJs($layout);

    // We clear these variables in order to put css and js for other modules that will not be cached (in case there are css and js imported in the layout)
    self::$js = self::$css = [];

    return $content;
  }

  /** Adds a debug bar at the top of the template
   *
   * @param string $debugBar Debug bar template
   */
  private function addDebugBar($debugBar)
  {
    ob_start();
    // send variables to the debug toolbar (if debug is active, cache don't)
    require $debugBar;
    parent::$template = (false === strpos(parent::$template, 'body'))
                        ? ob_get_clean() . parent::$template
                        : preg_replace('`(<body[^>]*>)`', '$1' . ob_get_clean(), parent::$template);

    parent::$template = str_replace(
      '/title>',
      '/title>'. self::addCss(false),
      parent::$template . self::addJs(false)); // suppress useless spaces
  }


  /** Includes the layout */
  private function layout() { parent::$layout = $this->buildCachedFile(LAYOUT, [], null, false); }

  /** Adds a css script to the existing ones
   *
   * @param array $css The css file to add (Array of string)
   */
  protected static function css($css = [])
  {
    if(!is_array($css)) $css = [$css];

    array_splice(self::$css, count(self::$css), 0, $css);
  }

  /** Puts the css into the template
   *
   * @param bool $firstTime If it's not the layout, often the first time we arrive at that function.
   *
   * @return string The links to the css files or the style markup with the css inside
   */
  private function addCss($firstTime)
  {
    $route = Routes::$_;
    $debugContent = '';

    if($firstTime)
    {
      if(isset($route[$this->route]))
      {
        $route = $route[$this->route];

        if(isset($route['resources']))
        {
          $chunks = $route['chunks'];
          $resources = $route['resources'];
          $debLink = "\n" . '<link rel="stylesheet" href="';
          $i = 0;
          $unorderedArray = $orderedArray = [];
          $debLink2 = $debLink . '/bundles/';

          if(isset($resources['bundle_css']))
          {
            foreach($resources['bundle_css'] as $key => $bundle_css)
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

          if(isset($resources['module_css']))
          {
            foreach($resources['module_css'] as $key => $module_css)
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

          if(isset($resources['_css']))
          {
            foreach($resources['_css'] as $key => $css)
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

          if(isset($resources['core_css']))
          {
            foreach($resources['core_css'] as $key => $core_css)
            {
              self::updateScriptsArray(
                $unorderedArray,
                $orderedArray,
                $i,
                $key,
                $debLink . '/lib/myLibs/core/css/' . $core_css . '.css" />'
              );
            }
          }

          $cssArray = self::calculateArray($unorderedArray, $orderedArray);

          foreach($cssArray as $css) { $debugContent .= $css; }
        }
      }
    }

    if(empty(self::$css)) return $debugContent;

    foreach(self::$css as $css) { $debugContent .= "\n" . '<link rel="stylesheet" href="' . $css . '.css" />'; }

    return $debugContent;
  }

  /**
   * Adds one or more javascript scripts to the existing ones. If the keys are string il will add the string to the link.
   *
   * @param array $js The javascript file to add (Array of strings)
   *
   * @return string The link to the js file or the script markup with the js inside
   */
  protected static function js($js = [])
  {
    if(!is_array($js)) $js = [$js];

    self::$js = array_merge(self::$js, $js);
  }

  /**
   * Uses calculations in order to put scripts in correct order that has been specified in the routes configuration file
   *
   * @param array $unorderedArray Unordered array of files
   * @param array $orderedArray   Ordered array of files
   *
   * @return array $scripts Final array
   */
  private static function calculateArray(array $unorderedArray, array $orderedArray)
  {
    $scripts = [];

    for($i = 0, $max = count($unorderedArray) + count($orderedArray); $i< $max; ++$i )
    {
      if(in_array($i, array_keys($orderedArray)))
      {
        $scripts[$i] = $orderedArray[$i];
        unset($orderedArray[$i]);
      } else
        $scripts[$i] = array_shift($unorderedArray);
    }

    return $scripts;
  }

  /**
   *
   */
  private static function updateScriptsArray(&$unorderedArray, &$orderedArray, &$i, $key, $code)
  {
    if(is_string($key))
      $orderedArray[intval(substr($key,1))] = $code;
    else
      $unorderedArray[$i] = $code;

    ++$i;
  }

  /** Puts the css into the template. Updates parent::$template.
   *
   * @param bool $firstTime If it's not the layout, often the first time we arrive at that function.
   *
   * @return The links to the js files or the script markup with the js inside
   */
  private function addJs($firstTime)
  {
    $route = Routes::$_;
    $debugContent = '';

    if($firstTime)
    {
      if(isset($route[$this->route]))
      {
        $route = $route[$this->route];

        if(isset($route['resources']))
        {
          $chunks = $route['chunks'];
          $resources = $route['resources'];
          $debLink = "\n" . '<script src="';
          $i = 0;
          $unorderedArray = $orderedArray = [];
          $debLink2 = $debLink . '/bundles/';

          if(isset($resources['bundle_js']))
          {
            foreach($resources['bundle_js'] as $key => $bundleJs)
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

          if(isset($resources['module_js']))
          {
            foreach($resources['module_js'] as $key => $module_js)
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

          if(isset($resources['_js']))
          {
            foreach($resources['_js'] as $key => $js)
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


          if(isset($resources['core_js']))
          {
            foreach($resources['core_js'] as $key => $core_js)
            {
              self::updateScriptsArray(
                $unorderedArray,
                $orderedArray,
                $i,
                $key,
                $debLink . '/lib/myLibs/core/js/' . $core_js . '.js" ></script>'
              );
            }
          }

          $jsArray = self::calculateArray($unorderedArray, $orderedArray);

          foreach($jsArray as $js) { $debugContent .= $js; }
        }
      }
    }

    foreach(self::$js as $key => $js)
    {
      // If the key don't give info on async and defer then put them automatically
      if(is_int($key))
        $key = '';
      $debugContent .= "\n" . '<script src="' . $js . '.js" ' . $key . '></script>';
    }

    return $debugContent;
  }
}
?>
