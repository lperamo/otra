<?php
declare(strict_types=1);
namespace otra;

use Exception;

/** A classic MVC production controller class
 *
 * @author Lionel Péramo
 * @package otra\controllers
 */
trait ProdControllerTrait
{
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
   * @throws OtraException
   */
  final public function renderView(string $file, array $variables = [], bool $ajax = false, bool $viewPath = true) : string
  {
    [$templateFile, ] = $this->getTemplateFile($file, $viewPath);

    if (!file_exists($templateFile))
    {
      require CORE_PATH . 'Logger.php';
      Logger::log('Problem when loading the file : ' . $templateFile);
      // TODO Have a beautiful error page for that case !
      throw new OtraException(
        'Server problem : the requested file does not exist ! Please wait for the re-establishment of the file, sorry for the inconvenience.'
      );
    }

    parent::handleCache($templateFile, $variables, $ajax, $this->route, $this->viewResourcePath);

    addCspHeader($this->route, $this->routeSecurityFilePath);
    addFeaturePoliciesHeader($this->route, $this->routeSecurityFilePath);

    return parent::$template;
  }

  /**
   * @param string $route
   *
   * @throws Exception
   * @return array
   */
  protected static function getTemplateResources(string $route) : array
  {
    return [
      self::addCss($route),
      self::addJs($route)
    ];
  }

  /**
   * Returns the pre-generated css and the additional concatenated css
   *
   * @param string $route
   *
   * @throws Exception
   * @return string The links to the css files or the style markup with the css inside
   */
  private static function addCss(string $route) : string
  {
    // If we have CSS files to load, then we load them
    return self::$hasCssToLoad ? '<link rel="stylesheet" nonce="' . getRandomNonceForCSP('style-src') .
      '" href="' . parent::getCacheFileName($route,'/cache/css/', VERSION, '.gz') . '" />' : '';
//    if(strlen($allCss) < RESOURCE_FILE_MIN_SIZE)
//      return '<style>' . $allCss . '</style>';
  }

  /** Returns the pre-generated js and the additional concatenated js
   *
   * @param string $route
   *
   * @throws Exception
   * @return string The links to the js files or the script markup with the js inside
   */
  private static function addJs(string $route) : string
  {
    // If we have JS files to load, then we load them
    $content = '';

    if (self::$hasJsToLoad)
      $content = parent::LABEL_SCRIPT_NONCE . getRandomNonceForCSP() . '" src="' .
        parent::getCacheFileName(
          $route,
          '/cache/js/',
          VERSION,
          '.gz'
        ) . '" async defer></script>';

    // If there are no scripts loaded dynamically (not from the routes configuration)
    if (empty(self::$javaScript))
      return $content;

    $allJs = '';

    foreach(self::$javaScript as $javaScript)
    {
      ob_start();

      if (!str_contains($javaScript, ('http')))
        echo file_get_contents(parent::$path . $javaScript);
      else
      {
        $curlHandle = curl_init($javaScript);
        curl_setopt($curlHandle, CURLOPT_HEADER, false);
        curl_exec($curlHandle);
        curl_close($curlHandle);
      }

      $allJs .= ob_get_clean();
    }

    if (strlen($allJs) < RESOURCE_FILE_MIN_SIZE)
      return parent::LABEL_SCRIPT_NONCE . getRandomNonceForCSP() . '" async defer>' . $allJs . '</script>';

    // Creates/erase the corresponding cleaned js file
    file_put_contents(parent::getCacheFileName($route, CACHE_PATH . 'js/', '_dyn', '.js'), $allJs);

    return $content . parent::LABEL_SCRIPT_NONCE . getRandomNonceForCSP() . '" src="' .
      parent::getCacheFileName($route, '/cache/js/', '_dyn', '.js') .
      '" async defer></script>';
  }
}
