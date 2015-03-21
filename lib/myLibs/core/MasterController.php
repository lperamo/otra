<?
/** MVC master controller class
 *
 * @author Lionel Péramo
 */
namespace lib\myLibs\core;

class MasterController
{
  public static $path;
  public $route,
        $bundle = '',
        $module = '',
        $viewPath = '/'; // index/index/ for indexController and indexAction

  protected $controller = '',
    $action = '',
    $getParams = '',
    $viewCSSPath = '/', // CSS path for this module
    $viewJSPath = '/', // JS path for this module
    $pattern = '';

  protected static
    $css = [],
    $js = [],
    $rendered = [],
    $ajax = false,
    $id,
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
  public function __construct(array $baseParams = [], array $getParams = [])
  {
    // If a controller is specified (in the other case, the calling controller is the Bootstrap class)
    if(isset($baseParams['controller']))
    {
      // Stores the bundle, module, controller and action for later use
      list($this->pattern, $this->bundle, $this->module, $this->controller, , $this->route, $this->chkJs, $this->chkCss) = array_values($baseParams);

      $this->action = substr($baseParams['action'], 0, -6);

      self::$id = $this->bundle . $this->module . $this->controller . $this->action;
      $this->getParams = $getParams;

      $mainPath = '/bundles/' . $this->bundle . '/' . $this->module . '/';
      // Stores the templates' path of the calling controller
      $this->viewPath = BASE_PATH . $mainPath . 'views/' . $this->controller . '/';
      $this->viewCSSPath = $mainPath .'resources/css/';
      $this->viewJSPath = $mainPath . 'resources/js/';

      self::$path = $_SERVER['DOCUMENT_ROOT'] . '..';

      // runs the preexecute function if exists and then the action
      $this->preExecute();
      // dump($getParams, $baseParams);die;
      call_user_func_array([$this, $baseParams['action']], $getParams);
    }
  }

  // To overload in the child class (e.g: in articleController)
  public function preExecute(){}

  /**
   * Encodes the value passed as parameter in order to create a cache file name
   *
   * @param string $filename File name to modify
   * @param string $path     File's path
   * @param stirng $prefix   Prefix of the file name
   *
   * @return string The cache file name version of the file
   */
  protected static function getCacheFileName($filename, $path = CACHE_PATH, $prefix = '', $extension = '.cache') {
    return $path . sha1('ca' . $prefix . $filename . 'che') . $extension;
  }

  /**
   * If the file is in the cache and is "fresh" then gets it. WE HAVE TO HAVE All_Config::$cache TO TRUE !!
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

  /**
   * Replaces the layout body content by the template body content if the layout is set
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

  /**
   * Sets the body attributes
  *
  * @param string $attrs
  */
  public static function bodyAttrs($attrs = '') { self::$bodyAttrs = $attrs; }

  /**
   * Sets the body content
   *
   * @param string $content
   */
  private static function body($content = '') { self::$body = $content; }

  /**
   * Sets the title of the page
   *
   * @param string $title
   */
  protected static function title($title)
  {
    self::$layout = (isset(self::$layout))
      ? preg_replace('@(<title>)(.*)(</title>)@', '$1' . $title . '$3', self::$layout)
      : '<title>' . $title . '</title><body>';
  }

  /**
   * Sets the favicons of the site
   *
   * @param string $filename
   * @param string $filenameIE
   */
  protected static function favicon($filename = '', $filenameIE = '')
  {
    echo '<link rel="icon" type="image/png" href="' , $filename , '" />
      <!--[if IE]><link rel="shortcut icon" type="image/x-icon" href="' , $filenameIE . '" /><![endif]-->';
  }

  /**
   * Adds a css script to the existing ones
   *
   * @param array $css The css file to add (Array of string)
   */
  protected static function css($css = [])
  {
    array_splice(self::$css, count(self::$css), 0, (is_array($css)) ? $css : [$css]);
  }

  /**
   * Adds one or more javascript scripts to the existing ones. If the keys are string il will add the string to the link.
   *
   * @param array $js The javascript file to add (Array of strings)
   *
   * @return string The link to the js file or the script markup with the js inside
   */
  protected static function js($js = []) {
    self::$js = array_merge(self::$js, (is_array($js)) ? $js : [$js]);
  }
}
?>
