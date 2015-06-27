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
  private static $cache_used;

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
  public final function renderView($file, array $variables = [], $ajax = false, $viewPath = true)
  {
    $templateFile = ($viewPath) ? $this->viewPath . $file : $file;

    if(!file_exists($templateFile))
    {
      require BASE_PATH . '/lib/myLibs/core/Logger.php';
      Logger::log('Problem when loading the file : ' . $templateFile);
      die('Server problem : the file requested doesn\'t exist ! Please wait for the re-establishment of the file, sorry for the inconvenience.');
    }

    // If we already have the template in memory and that it's not empty then we show it
    self::$cache_used = isset(self::$rendered[$templateFile]) && '' != self::$rendered[$templateFile];

    if($ajax)
      $this->ajax = $ajax;

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
   * @param string $filename
   * @param array  $variables Variables to pass to the template
   * @param sting  $cacheFile The cache file name version of the file
   * @param bool   $layout    If we add a layout or not
   * @return mixed|string
   */
  private function buildCachedFile($filename, array $variables, $cachedFile = null, $layout = true)
  {
    extract($variables);
    ob_start();
    require $filename;

    $content = ($layout) ? self::addLayout(ob_get_clean()) : ob_get_clean();

    $routeV = $this->route . VERSION;

    // /!\ We have to put these functions in this order to put the css before ! (in order to optimize the loading)
    $content = preg_replace('/>\s+</', '><',
      (!$layout) ? str_replace('/title>', '/title>', $content)
                 : ($ajax
                   ? str_replace('/title>', '/title>'. $this->addCss($routeV), $content . $this->addJs($routeV))
                   : $this->addCss($routeV) . $content . $this->addJs($routeV))
                 ); // suppress useless spaces

    // We clear these variables in order to put css and js for other modules that will not be cached (in case there are css and js imported in the layout)
    self::$js = self::$css = [];

    if('cli' == PHP_SAPI)
      return $content;

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

    if(!(parent::$layout = parent::getCachedFile(LAYOUT, $cachedFile))) // if it was not in the cache or "fresh"...
      parent::$layout = $this->buildCachedFile(LAYOUT, [], $cachedFile, false);
  }

  /** Returns the pre-generated css and the additional concatenated css
   *
   * @param string $routeV Route name plus the version
   *
   * @return string The links to the css files or the style markup with the css inside
   */
  private function addCss($routeV)
  {
    $content = ($this->chkCss) ? '<link rel="stylesheet" href="' . parent::getCacheFileName($routeV, '/cache/css/', '', '.gz') . '" />' : '';

    if(empty(self::$css))
      return $content;

    $allCss = '';

    foreach(self::$css as $css) {
      $allCss .= file_get_contents(self::$path . $css . '.css');
    }

    if($firstTime)
      $allCss .= file_get_contents(parent::getCacheFileName($routeV, CACHE_PATH . 'css/', '', '.css'));

    if(strlen($allCss) < RESOURCE_FILE_MIN_SIZE)
      return '<style>' . $allCss . '</style>';

    $lastFile .= VERSION;
    $fp = fopen(parent::getCacheFileName($routeV, CACHE_PATH . 'css/', '_dyn', '.css'), 'w');
    fwrite($fp, $allCss);
    fclose($fp);

    return $content . '<link rel="stylesheet" href="' . parent::getCacheFileName($routeV, '/cache/css/', '_dyn', '.css') . '" />';
  }

  /** Returns the pre-generated js and the additional concatenated js
   *
   * @param string $routeV Route name plus the version
   *
   * @return The links to the js files or the script markup with the js inside
   */
  private function addJs($routeV)
  {
    $content = ($this->chkJs) ? '<script type="application/javascript;version=1.7" src="' . parent::getCacheFileName($routeV, '/cache/js/', '', '.gz') . '" async defer></script>' : '';
    if(empty(self::$js))
      return $content;

    $allJs = '';

    foreach(self::$js as $js)
    {
      $lastFile = $js . '.js';
      ob_start();
      if(false === strpos($lastFile, ('http')))
        echo file_get_contents(parent::$path . $lastFile);
      else{
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $lastFile);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_exec($ch);
        curl_close($ch);
      }
      $allJs .= ob_get_clean();
    }

    if($firstTime)
      $allJs .= file_get_contents(parent::getCacheFileName($routeV, CACHE_PATH . 'js/', '', '.js'));

    if(strlen($allJs) < RESOURCE_FILE_MIN_SIZE)
      return '<script async defer>' + $allJs + '</script>';
    $lastFile .= VERSION;
    // Creates/erase the corresponding cleaned js file
    $fp = fopen(parent::getCacheFileName($routeV, CACHE_PATH . 'js/', '_dyn', '.js'), 'w');
    fwrite($fp, $allJs);
    fclose($fp);

    return $content . '<script src="' . parent::getCacheFileName($routeV, '/cache/js/', '_dyn', '.js') . '" async defer></script>';
  }
}
?>
