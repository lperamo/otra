<?
/** A classic MVC production controller class
 *
 * @author Lionel Péramo
 */
namespace lib\myLibs\core;

use config\All_Config,
    lib\packerjs\JavaScriptPacker;

class Controller
{
  protected $bundle = '',
    $module = '',
    $controller = '',
    $action = '',
    $getParams = '',
    $viewCSSPath = '/', // CSS path for module
    $viewJSPath = '/'; // JS path for module

  public $viewPath = '/', // index/index/ for indexController and indexAction
    $route = '';

  public static $path;
  private static $id,
    $cache_used,
    /* @var string $template The actual template being processed */
    $template,
    $title,
    $favicon,
    $css = array(),
    $js = array(),
    //  $css = '';
    //  $js = '';
    $body,
    $bodyAttrs,
    $layout,
    $rendered = array();

  /**
   * @param array $baseParams [
   *  'bundle' => $bundle,
   *  'controller' => $controller,
   *  'action' => $action]
   *
   * @param array $getParams The params passed by GET method
   */
  public function __construct(array $baseParams = array(), array $getParams = array())
  {
    header('Content-Type: text/html; charset=utf-8');
    header("Vary: Accept-Encoding,Accept-Language");

    // If a controller is specified (in the other case, the calling controller is the Bootstrap class)
    if(isset($baseParams['controller']))
    {
      // Stores the bundle, module, controller and action for later use
      list($this->pattern, $this->bundle, $this->module, $this->controller, , $this->route) = array_values($baseParams);
      $this->action = substr($baseParams['action'], 0, -6);

      self::$id = $this->bundle . $this->module . $this->controller . $this->action;
      $this->getParams = $getParams;

      $mainPath = 'bundles/' . $this->bundle . '/modules/' . $this->module . '/';
      // Stores the templates' path of the calling controller
      $this->viewPath = BASE_PATH2 . $mainPath . 'views/' . $this->controller . '/';
      $this->viewCSSPath = '/' . $mainPath .'media/css/';
      $this->viewJSPath = '/'. $mainPath . 'media/js/';

      self::$path = $_SERVER['DOCUMENT_ROOT'] . '..';

      // runs the preexecute function if exists and then the action
      $this->preExecute();
      call_user_func_array(array($this, $baseParams['action']), $getParams);
    }
  }

  // To overload in the child class (e.g: in articleController)
  public function preExecute(){}

  /**
   * If the files are in cache, put them directly in $rendered
   *
   * @param array $filesToCheck Files to check in cache
   *
   * @return bool True if ALL the files are in cache, false otherwise
   */
  public function checkCache(array $filesToCheck)
  {
    foreach($filesToCheck as $fileToCheck)
    {
      $templateFile = $this->viewPath . $fileToCheck;

      $cachedFile = self::getCacheFileName($templateFile);
      if (file_exists($cachedFile))
      {
        self::$rendered[$templateFile] = self::getCachedFile($cachedFile, true);
        if(!self::$rendered[$templateFile])
          return false;
      }else
        return false;
    }
    return true;
  }

  /** Renders a view. NB: Even is cache is activated, the template can be not fresh !
   *
   * @param string $file      The file to render
   * @param array  $variables Variables to pass
   * @param bool   $ajax      Is this an ajax partial ?
   * @param string $viewPath  Using the view path or not
   *
   * return string self::$template Content of the template
   */
  public final function renderView($file, array $variables = array(), $ajax = false, $viewPath = true)
  {
    $templateFile = ($viewPath) ? $this->viewPath . $file : $file;
    if(!file_exists($templateFile))
      die('Server problem : the file requested doesn\'t exist ! Please wait for the re-establishment of the file, sorry for the inconvenience.');

    // If we already have the template in memory and that it's not empty then we show it
    self::$cache_used = isset(self::$rendered[$templateFile]) && '' != self::$rendered[$templateFile];

    if(self::$cache_used)
      self::$template = self::$rendered[$templateFile];
    else
    {
      $cachedFile = self::getCacheFileName($templateFile);
      self::$template = (!self::getCachedFile($cachedFile)) ? $this->buildCachedFile($templateFile, $variables, $cachedFile)
                                                            : self::getCachedFile(self::getCacheFileName($templateFile), true);
    }

    return self::$template;
  }

  /** Encodes the value passed as parameter in order to create a cache file name
   *
   * @param string $filename File name to modify
   * @param string $path     File's path
   * @param stirng $prefix   Prefix of the file name
   * @return string The cache file name version of the file
   */
  private static function getCacheFileName($filename, $path = CACHE_PATH, $prefix = '', $extension = '.cache')
  {
    return $path . sha1('ca' . $prefix . $filename . 'che') . $extension;
  }

  /** If the file is in the cache and is "fresh" then gets it. WE HAVE TO HAVE All_Config::$cache TO TRUE !!
   *
   * @param string  $cacheFile The cache file name version of the file
   * @param bool    $exists    True if we know that the file exists.
   *
   * @return string|bool $content The cached (and cleaned) content if exists, false otherwise
   */
  private static function getCachedFile($cachedFile, $exists = false)
  {
    if(($exists || file_exists($cachedFile)) && (filemtime($cachedFile) + CACHE_TIME) > time())
      return file_get_contents ($cachedFile);

    return false;
  }

  /** Checks if the cached file exists and if it's fresh
   *
   * @param string $cachedFile The cache file name version of the file
   *
   * @return bool True if it exists and it's fresh, false otherwise.
   */
  private static function isCachedFileFresh($cachedFile)
  {
    return (file_exists($cachedFile) && (filemtime($cachedFile) + CACHE_TIME) > time());
  }

  /** Parses the template file and updates self::$template
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

    $content = ($layout) ? self::addLayout(ob_get_clean()) : ob_get_clean();

    // /!\ We have put to put these functions in this order to put the css before ! (in order to optimize the loading)
    $content = preg_replace('/>\s+</', '><', str_replace(
      '/title>',
      '/title>'. self::addCss(),
      $content . self::addJs())); // suppress useless spaces

    // We clear these variables in order to put css and js for other modules that will not be cached (in case there are css and js imported in the layout)
    self::$js = self::$css = array();

    if(null != $cachedFile)
    {
      $fp = fopen($cachedFile, 'w');
      fwrite($fp, $content);
      fclose($fp);
    }

    return $content;
  }

  /** Replaces the layout body content by the template body content if the layout is set
   *
   * @param string $content Content of the template to process
   */
  private static function addLayout($content)
  {
    return (isset(self::$layout))
      ? preg_replace('`(<body[^>]*>)(.*)`s', '$1' . str_replace('$','\\$', $content), self::$layout)
      : $content;
  }

  /** Includes the layout */
  private function layout()
  {
    $cachedFile = self::getCacheFileName('layout.phtml', CACHE_PATH, 'CORE_FRAMEWORK');
    self::$layout = self::getCachedFile(LAYOUT, $cachedFile);
    if(!self::$layout) // if it was not in the cache or "fresh"...
      self::$layout = $this->buildCachedFile(LAYOUT, array(), $cachedFile, false);
  }

  /** Sets the title of the page
   *
   * @param string $title Title of the page
   */
  private static function title($title) {
    self::$layout = (isset(self::$layout))
      ? preg_replace('@(<title>)(.*)(</title>)@', '$1' . $title . '$3', self::$layout)
      : '<title>' . $title . '</title><body>';
  }

  /** Sets the favicons of the site
   *
   * @param string $favicon   Favicon file name
   * @param string $faviconIE Favicon file name for IE
   */
  private static function favicon($favicon = '', $faviconIE = '')
  {
    echo '<link rel="icon" type="image/png" href="' , $favicon , '" />
      <!--[if IE]><link rel="shortcut icon" type="image/x-icon" href="' , $faviconIE . '" /><![endif]-->';
  }

  /** Adds a css script to the existing ones
   *
   * @param array $css The css file to add (Array of string)
   */
  protected static function css($css = array())
  {
    if(!is_array($css))
      $css = array($css);

    array_splice(self::$css, count(self::$css), 0, $css);
  }

  /**
   * Puts the css into the template
   *
   * @return string The links to the css files or the style markup with the css inside
   */
  private static function addCss()
  {
    if(empty(self::$css))
      return '';

    // Concatenates all the css
    $debugContent = $finalCss = '';

    foreach(self::$css as $css)
    {
      $lastFile = self::$path . $css . '.css';

      ob_start();
      require $lastFile;
      $finalCss .= ob_get_clean();
    }

    if(strlen($finalCss) < RESOURCE_FILE_MIN_SIZE)
      return '<style>' . $finalCss . '</style>';
    else
    {
      $lastFile .= VERSION;
      $fp = fopen(self::getCacheFileName($lastFile, CACHE_PATH, self::$id, '.css'), 'w');
      fwrite($fp, $finalCss);
      fclose($fp);

      return '<link rel="stylesheet" href="' . self::getCacheFileName($lastFile, '/cache/', self::$id, '.css') . '" />';
    }
  }

  /**
   * Cleans the css (spaces and comments)
   *
   * @param $content The css content
   *
   * @return string The cleaned css
   */
  private static function cleanCss($content)
  {
    $content = preg_replace('@/\*.*?\*/@s', '', $content);
    $content = str_replace(array("\r\n", "\r", "\n", "\t", '  '), '', $content);
    $content = str_replace(array('{ ',' {'), '{', $content);
    $content = str_replace(array(' }','} '), '}', $content);
    $content = str_replace(array('; ',' ;'), ';', $content);
    $content = str_replace(array(', ',' ,'), ',', $content);

    return str_replace(': ', ':', $content);
  }

  /**
   * Sets the body attributes
   *
   * @param string $attrs Body attributes
   */
  public static function bodyAttrs($attrs = '') { self::$bodyAttrs = $attrs; }

  /** Sets the body content
   *
   * @param string $content Body content
   */
  private static function body($content = '') { self::$body = $content; }

  /** Adds one or more javascript scripts to the existing ones. If the keys are string il will add the string to the link.
   *
   * @param array $js The javascript file to add (Array of strings)
   *
   * @return string The link to the js file or the script markup with the js inside
   */
  protected static function js($js = array())
  {
    if(!is_array($js))
      $js = array($js);

    self::$js = array_merge(self::$js, $js);
  }

  /** Puts the css into the template. Updates self::$template.
   *
   * @return The links to the js files or the script markup with the js inside
   */
  private function addJs()
  {
    if(empty(self::$js))
      return '';

    $debugContent = $finalJs = '';

    $tmp = ini_get('allow_url_include');
    ini_set('allow_url_include', 1);
    foreach(self::$js as $js)
    {
      $lastFile = $js . '.js';
      ob_start();
      require $lastFile;
      $finalJs .= ob_get_clean();
    }
    ini_set('allow_url_include', $tmp);

    if(strlen($finalJs) < RESOURCE_FILE_MIN_SIZE)
      return '<script async defer>' . $finalJs . '</script>';
    else
    {
      $lastFile .= VERSION;
      // Creates/erase the corresponding cleaned js file
      $fp = fopen(self::getCacheFileName($lastFile, CACHE_PATH, self::$id, '.js'), 'w');
      fwrite($fp, $finalJs);
      fclose($fp);

      return '<script src="' . self::getCacheFileName($lastFile, '/cache/', self::$id, '.js') . '" async defer></script>';
    }
  }

  /**
   * Cleans the js (spaces and comments)
   * TODO optimize with return new JavaScriptPacker($content)->pack();
   *
   * @param $content The js content
   *
   * @return string The cleaned js
   */
  private static function cleanJs($content)
  {
    $packer = new JavaScriptPacker($content);

    return $packer->pack();
  }

  /** Suppress HTML comments but keeping conditional comments
   *
   * @param string $content The HTML to clean
   *
   * @return string
   */
  private function cleanHTML($content) { return preg_replace('#<!--[^\[\]]*-->#', '', $content); }
}
?>
