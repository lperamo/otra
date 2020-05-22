<?php
/** A classic MVC production controller class
 *
 * @author Lionel Péramo
 */
declare(strict_types=1);
namespace otra;

use config\AllConfig;

/**
 * @package otra
 */
trait ProdControllerTrait
{
  private static bool $cache_used;

  /** If the files are in cache, put them directly in $rendered
   *
   * @param array $filesToCheck Files to check in cache
   *
   * @return bool True if ALL the files are in cache, false otherwise
   */
  public function checkCache(array $filesToCheck) : bool
  {
    foreach($filesToCheck as &$fileToCheck)
    {
      $templateFile = $this->viewPath . $fileToCheck;
      $cachedFile = parent::getCacheFileName($templateFile);

      if (false === file_exists($cachedFile))
        return false;

      self::$rendered[$templateFile] = parent::getCachedFile($cachedFile, true);

      if (false === self::$rendered[$templateFile])
        return false;
    }

    return true;
  }

  /** Renders a view. NB: Even is cache is activated, the template can be not fresh !
   *
   * @param string $file      The file to render
   * @param array  $variables Variables to pass
   * @param bool   $ajax      Is this an ajax partial ?
   * @param bool   $viewPath  If true, we adds the usual view path before the $file variable.
   *
   * return string parent::$template Content of the template
   *
   * @return string
   */
  final public function renderView(string $file, array $variables = [], bool $ajax = false, bool $viewPath = true) : string
  {
    if (strpos($this->route, 'otra_') === false)
      $templateFile = ($viewPath === true) ? $this->viewPath . $file : $file;
    else
      $templateFile = CORE_VIEWS_PATH . $this->controller . '/' . $file;

    if (false === file_exists($templateFile))
    {
      require CORE_PATH . 'Logger.php';
      Logger::log('Problem when loading the file : ' . $templateFile);
      // TODO Have a beautiful error page for that case !
      die('Server problem : the file requested does not exist ! Please wait for the re-establishment of the file, sorry for the inconvenience.');
    }

    // If we already have the template in memory and that it's not empty then we show it
    self::$cache_used = true === isset(self::$rendered[$templateFile]) && '' != self::$rendered[$templateFile];

    if (true === $ajax)
      self::$ajax = $ajax;

    // If we already have the file in 'cache memory' then we serve it
    if (true === self::$cache_used)
      parent::$template = self::$rendered[$templateFile];
    else // otherwise if we have the file in a 'cache file' then we serve it, otherwise we build the 'cache file'
    {
      $cachedFile = parent::getCacheFileName($templateFile);
      parent::$template = (false === parent::getCachedFile($cachedFile)
        || (property_exists(AllConfig::class, 'cache') === true && AllConfig::$cache === false))
        ? $this->buildCachedFile($templateFile, $variables, $cachedFile)
        : parent::getCachedFile(parent::getCacheFileName($templateFile), true);
    }

    parent::addCspHeader();
    parent::addFeaturePoliciesHeader();

    return parent::$template;
  }

  /** Parses the template file and updates parent::$template
   *
   * @param string $templateFilename
   * @param array  $variables  Variables to pass to the template
   * @param string $cachedFile The cache file name version of the file
   * @param bool   $layout     If we add a layout stored previously or not
   *
   * @return mixed|string
   */
  private function buildCachedFile(string $templateFilename, array $variables, $cachedFile = null, bool $layout = true) : string
  {
    $content = MasterController::processFinalTemplate($templateFilename, $variables);

    $routeV = $this->route . VERSION;

    // /!\ We have to put these functions in this order to put the css before ! (in order to optimize the loading)
    $content = preg_replace('/>\s+</', '><',
      ($layout === true)
        ? false === self::$ajax
        ? str_replace(
          '/title>',
          '/title>'. self::addCss($routeV),
          $content . self::addJs($routeV))
        : self::addCss($routeV) . $content . self::addJs($routeV)
        : $content
    ); // suppress useless spaces

    // We clear these variables in order to put css and js for other modules that will not be cached (in case there are css and js imported in the layout)
    self::$js = self::$css = [];

    if ('cli' === PHP_SAPI)
      return $content;

    // If the cached filename is specified and if the cache is activated, we create a cached file.
    if (null !== $cachedFile
      && (property_exists(AllConfig::class, 'cache') === false || AllConfig::$cache === true))
      file_put_contents($cachedFile, $content);

    return $content;
  }

  /** Returns the pre-generated css and the additional concatenated css
   *
   * @param string $routeV Route name plus the version
   *
   * @return string The links to the css files or the style markup with the css inside
   */
  private function addCss(string $routeV) : string
  {
    // If we have CSS files to load, then we load them
    return self::$hasCssToLoad ? '<link rel="stylesheet" href="' . parent::getCacheFileName($routeV, '/cache/css/', '', '.gz') . '" />' : '';
//    if(strlen($allCss) < RESOURCE_FILE_MIN_SIZE)
//      return '<style>' . $allCss . '</style>';
  }

  /** Returns the pre-generated js and the additional concatenated js
   *
   * @param string $routeV Route name plus the version
   *
   * @return string The links to the js files or the script markup with the js inside
   */
  private function addJs(string $routeV) : string
  {
    // If we have JS files to load, then we load them
    $content = (self::$hasJsToLoad) ? '<script type="application/javascript" src="' . parent::getCacheFileName($routeV, '/cache/js/', '', '.gz') . '" async defer></script>' : '';

    if (true === empty(self::$js))
      return $content;

    $allJs = '';

    foreach(self::$js as &$js)
    {
      $lastFile = $js . '.js';
      ob_start();

      if (false === strpos($lastFile, ('http')))
        echo file_get_contents(parent::$path . $lastFile);
      else
      {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $lastFile);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_exec($ch);
        curl_close($ch);
      }
      $allJs .= ob_get_clean();
    }

    if (strlen($allJs) < RESOURCE_FILE_MIN_SIZE)
      return '<script async defer>' + $allJs + '</script>';

    $lastFile .= VERSION;

    // Creates/erase the corresponding cleaned js file
    file_put_contents(parent::getCacheFileName($routeV, CACHE_PATH . 'js/', '_dyn', '.js'), $allJs);

    return $content . '<script src="' . parent::getCacheFileName($routeV, '/cache/js/', '_dyn', '.js') . '" async defer></script>';
  }
}

