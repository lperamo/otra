<?
/** A classic MVC development controller class
 *
 * @author Lionel Péramo
 */
namespace lib\myLibs\core;

use config\All_Config,
    lib\myLibs\core\Logger,
    config\Routes,
    lib\myLibs\core\MasterController;

class Controller extends MasterController
{
  public $viewPath = '/'; // index/index/ for indexController and indexAction

  private static $css = array(),
    $js = array(),
    $rendered = array();

  public function __construct(array $baseParams = array(), array $getParams = array()){
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
  public final function renderView($file, array $variables = array(), $ajax = false, $viewPath = true)
  {
    $templateFile = ($viewPath) ? $this->viewPath . $file : $file;
    Logger::logTo("\t" . 'Ajax : ' . (($ajax) ? 'true' : 'false'), 'trace');

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
    $content = str_replace(
      '/title>',
      '/title>'. self::addCss($layout),
      $content . self::addJs($layout));

    // We clear these variables in order to put css and js for other modules that will not be cached (in case there are css and js imported in the layout)
    self::$js = self::$css = array();

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
  private function layout() { parent::$layout = $this->buildCachedFile(LAYOUT, array(), null, false); }

  /** Adds a css script to the existing ones
   *
   * @param array $css The css file to add (Array of string)
   */
  protected static function css($css = array())
  {
    if(!is_array($css)) $css = array($css);

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
      if(isset($route[$this->route])){
        $route = $route[$this->route];
        if(isset($route['resources']))
        {
          $resources = $route['resources'];
          if(isset($resources['cmsCss'])) {
            foreach($resources['cmsCss'] as $cmsCss) {
              $debugContent .= "\n" . '<link rel="stylesheet" href="' . CMS_CSS_PATH . $cmsCss . '.css" />';
            }
          }
          if(isset($resources['css'])) {
            foreach($resources['css'] as $css) {
              $debugContent .= "\n" . '<link rel="stylesheet" href="' . $css . '.css" />';
            }
          }
        }
      }
    }

    if(empty(self::$css)) return $debugContent;

    foreach(self::$css as $css) { $debugContent .= "\n" . '<link rel="stylesheet" href="' . $css . '.css" />'; }

    return $debugContent;
  }

  /** Adds one or more javascript scripts to the existing ones. If the keys are string il will add the string to the link.
   *
   * @param array $js The javascript file to add (Array of strings)
   *
   * @return string The link to the js file or the script markup with the js inside
   */
  protected static function js($js = array())
  {
    if(!is_array($js)) $js = array($js);

    self::$js = array_merge(self::$js, $js);
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
      if(isset($route[$this->route])){
        $route = $route[$this->route];
        if(isset($route['resources']))
        {
          $resources = $route['resources'];
          if(isset($resources['firstJs'])) {
            foreach($resources['firstJs'] as $js) {
              $debugContent .= "\n" . '<script src="' .  $js . '.js" ></script>';
            }
          }
          if(isset($resources['cmsJs'])) {
            foreach($resources['cmsJs'] as $cmsJs) {
              $debugContent .= "\n" . '<script src="' . CMS_JS_PATH . $cmsJs . '.js" ></script>';
            }
          }
          if(isset($resources['js'])) {
            foreach($resources['js'] as $js) {
              $debugContent .= "\n" . '<script src="' .  $js . '.js" ></script>';
            }
          }
        }
      }
    }

    if(empty(self::$js)) return $debugContent;

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
