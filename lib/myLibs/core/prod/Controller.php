<?
/** A classic MVC production controller class
 *
 * @author Lionel Péramo
 */
namespace lib\myLibs\core;

use config\All_Config,
  lib\myLibs\core\MasterController;

class Controller extends MasterController
{
  public $viewPath = '/'; // index/index/ for indexController and indexAction

  private static $cache_used,
    $css = array(),
    $js = array(),
    $rendered = array();

  /** If the files are in cache, put them directly in $rendered
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

      $cachedFile = parent::getCacheFileName($templateFile);
      if (file_exists($cachedFile))
      {
        self::$rendered[$templateFile] = parent::getCachedFile($cachedFile, true);
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
   * return string parent::$template Content of the template
   */
  public final function renderView($file, array $variables = array(), $ajax = false, $viewPath = true)
  {
    $templateFile = ($viewPath) ? $this->viewPath . $file : $file;
    if(!file_exists($templateFile))
      die('Server problem : the file requested doesn\'t exist ! Please wait for the re-establishment of the file, sorry for the inconvenience.');

    // If we already have the template in memory and that it's not empty then we show it
    self::$cache_used = isset(self::$rendered[$templateFile]) && '' != self::$rendered[$templateFile];

    if(self::$cache_used)
      parent::$template = self::$rendered[$templateFile];
    else
    {
      $cachedFile = parent::getCacheFileName($templateFile);
      parent::$template = (!parent::getCachedFile($cachedFile)) ? $this->buildCachedFile($templateFile, $variables, $cachedFile)
                                                              : parent::getCachedFile(parent::getCacheFileName($templateFile), true);
    }

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

    $content = ($layout) ? self::addLayout(ob_get_clean()) : ob_get_clean();

    // /!\ We have to put these functions in this order to put the css before ! (in order to optimize the loading)
    $content = preg_replace('/>\s+</', '><', str_replace(
      '/title>',
      '/title>'. $this->addCss($layout),
      $content . $this->addJs($layout))); // suppress useless spaces

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

  /** Includes the layout */
  private function layout()
  {
    $cachedFile = parent::getCacheFileName('layout.phtml', CACHE_PATH, 'CORE_FRAMEWORK');
    parent::$layout = parent::getCachedFile(LAYOUT, $cachedFile);
    if(!parent::$layout) // if it was not in the cache or "fresh"...
      parent::$layout = $this->buildCachedFile(LAYOUT, array(), $cachedFile, false);
  }

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
    if(empty(self::$css)){
      if(!$firstTime)
        return '';

      $cachedFile = parent::getCacheFileName($this->route . VERSION, CACHE_PATH . 'css/', '', '.css');
      ob_start();
      require $cachedFile;
      $css = ob_get_clean();

      if(strlen($css) < RESOURCE_FILE_MIN_SIZE)
        return '<style>' . $css . '</style>';

      return '<link rel="stylesheet" href="' . $cachedFile . '" />';
    }

    // Concatenates all the css
    $allCss = '';

    foreach(self::$css as $css)
    {
      $lastFile = self::$path . $css . '.css';

      ob_start();
      require $lastFile;
      $allCss .= ob_get_clean();
    }

    if($firstTime)
    {
      ob_start();
      require parent::getCacheFileName($this->route . VERSION, CACHE_PATH . 'css/', '', '.css');
      $allCss .= ob_get_clean();
    }

    if(strlen($allCss) < RESOURCE_FILE_MIN_SIZE)
      return '<style>' . $allCss . '</style>';

    $lastFile .= VERSION;
    $fp = fopen(parent::getCacheFileName($this->route . VERSION, CACHE_PATH . 'css/', '_dyn', '.css'), 'w');
    fwrite($fp, $allCss);
    fclose($fp);

    return '<link rel="stylesheet" href="' . parent::getCacheFileName($this->route . VERSION, '/cache/css/', '_dyn', '.css') . '" />';
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
   * @return The links to the js files or the script markup with the js inside
   */
  private function addJs($firstTime)
  {
    if(empty(self::$js)){
      if(!$firstTime)
        return '';

      $cachedFile = parent::getCacheFileName($this->route . VERSION, CACHE_PATH . 'js/', '', '.js');
      ob_start();
      require $cachedFile;
      $js = ob_get_clean();

      if(strlen($js) < RESOURCE_FILE_MIN_SIZE)
        return '<script async defer>' . $js . '</script>';

      return '<script src="' . parent::getCacheFileName($this->route . VERSION, '/cache/js/', '', '.js') . '" async defer></script>';
    }

    $allJs = '';

    foreach(self::$js as $js)
    {
      $lastFile = $js . '.js';
      ob_start();
      if(false === strpos($lastFile, ('http')))
        require parent::$path . $lastFile;
      else{
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $lastFile);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        // $data = curl_exec($ch);
        curl_exec($ch);
        curl_close($ch);
      }
      // require $lastFile;
      $allJs .= ob_get_clean();
      // var_dump('plop', $data, $allJs);die;
    }

    if($firstTime)
    {
      ob_start();
      require parent::getCacheFileName($this->route . VERSION, CACHE_PATH . 'js/', '', '.js');
      $allJs .= ob_get_clean();
    }

    if(strlen($allJs) < RESOURCE_FILE_MIN_SIZE)
      return '<script async defer>' . $allJs . '</script>';
    $lastFile .= VERSION;
    // Creates/erase the corresponding cleaned js file
    $fp = fopen(parent::getCacheFileName($this->route . VERSION, CACHE_PATH . 'js/', '_dyn', '.js'), 'w');
    fwrite($fp, $allJs);
    fclose($fp);

    return '<script src="' . parent::getCacheFileName($this->route . VERSION, '/cache/js/', '_dyn', '.js') . '" async defer></script>';
  }
}
?>
