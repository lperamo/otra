<?
/** A classic MVC master controller class
 *
 * @author Lionel Péramo
 */
namespace lib\myLibs\core;

class MasterController{
  protected $bundle = '',
    $module = '',
    $controller = '',
    $action = '',
    $route,
    $getParams = '',
    $viewCSSPath = '/', // CSS path for module
    $viewJSPath = '/', // JS path for module
    $pattern = '';
  public static $path;
  protected static $id,
  /* @var string $template The actual template being processed */
    $template,
    $layout,
    $body,
    $bodyAttrs,
    $layoutOnce = false;

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

  /** Encodes the value passed as parameter in order to create a cache file name
   *
   * @param string $filename File name to modify
   * @param string $path     File's path
   * @param stirng $prefix   Prefix of the file name
   * @return string The cache file name version of the file
   */
  protected static function getCacheFileName($filename, $path = CACHE_PATH, $prefix = '', $extension = '.cache')
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
  protected static function getCachedFile($cachedFile, $exists = false)
  {
    if(($exists || file_exists($cachedFile)) && (filemtime($cachedFile) + CACHE_TIME) > time())
      return file_get_contents ($cachedFile);

    return false;
  }

  /** Replaces the layout body content by the template body content if the layout is set
   *
   * @param string $content Content of the template to process
   */
  protected static function addLayout($content)
  {
    if(isset(self::$layout))
    {
      self::$layoutOnce = true;
      return preg_replace('`(<body[^>]*>)(.*)`s', '$1' . str_replace('$','\\$', $content), self::$layout);
    }else
      return $content;
  }

  /** Sets the body attributes
  *
  * @param string $attrs Body attributes
  */
  public static function bodyAttrs($attrs = '') { self::$bodyAttrs = $attrs; }

  /** Sets the body content
   *
   * @param string $content Body content
   */
  private static function body($content = '') { self::$body = $content; }

  /** Sets the title of the page
   *
   * @param string $title Title of the page
   */
  protected static function title($title) {
    self::$layout = (isset(self::$layout))
      ? preg_replace('@(<title>)(.*)(</title>)@', '$1' . $title . '$3', self::$layout)
      : '<title>' . $title . '</title><body>';
  }

  /** Sets the favicons of the site
   *
   * @param string $favicon   Favicon file name
   * @param string $faviconIE Favicon file name for IE
   */
  protected static function favicon($favicon = '', $faviconIE = '')
  {
    echo '<link rel="icon" type="image/png" href="' , $favicon , '" />
      <!--[if IE]><link rel="shortcut icon" type="image/x-icon" href="' , $faviconIE . '" /><![endif]-->';
  }
}
