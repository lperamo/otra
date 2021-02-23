<?php
declare(strict_types=1);
/** MVC master controller class
 *
 * @author Lionel Péramo
 */
namespace otra;

use cache\php\BlocksSystem;
use config\AllConfig;
use JetBrains\PhpStorm\Pure;

/**
 * @package otra
 */
abstract class MasterController
{
  public static string $path;
  public ?string $routeSecurityFilePath = null;
  public static array $nonces = [
    'script-src' => [],
    'style-src' => []
  ];

  public static bool $cacheUsed = false;

  public string $route,
    $url,
    $bundle = '',
    $module = '',
    $viewPath = '/'; // index/index/ for indexController and indexAction

  protected string $controller = '',
    $action = '',
    $pattern = ''; // path to the action, eg. "application/bundle/controller/action" => "HelloWorld/frontend/index/Home

  protected array
    $viewResourcePath = [
      'css' => '/', // CSS path for this module
      'js' => '/'  // JS path for this module
    ],
    $getParams = [];

  protected static array
    $stylesheets = [],
    $javaScript = [],
    $rendered = [];

  protected static bool
    $ajax = false,
    $hasJsToLoad,
    $hasCssToLoad;

  protected static string $layout;

  /* @var bool|string $template The actual template being processed */
  protected static bool|string $template;

  // mainly HTTP codes !
  public const
    OTRA_LABEL_ENDING_TITLE_TAG = '/title>',
    HTTP_CONTINUE = 100,
    HTTP_SWITCHING_PROTOCOLS = 101,
    HTTP_PROCESSING = 102,            // RFC2518
    HTTP_OK = 200,
    HTTP_CREATED = 201,
    HTTP_ACCEPTED = 202,
    HTTP_NON_AUTHORITATIVE_INFORMATION = 203,
    HTTP_NO_CONTENT = 204,
    HTTP_RESET_CONTENT = 205,
    HTTP_PARTIAL_CONTENT = 206,
    HTTP_MULTI_STATUS = 207,          // RFC4918
    HTTP_ALREADY_REPORTED = 208,      // RFC5842
    HTTP_IM_USED = 226,               // RFC3229
    HTTP_MULTIPLE_CHOICES = 300,
    HTTP_MOVED_PERMANENTLY = 301,
    HTTP_FOUND = 302,
    HTTP_SEE_OTHER = 303,
    HTTP_NOT_MODIFIED = 304,
    HTTP_USE_PROXY = 305,
    HTTP_RESERVED = 306,
    HTTP_TEMPORARY_REDIRECT = 307,
    HTTP_PERMANENTLY_REDIRECT = 308,  // RFC7238
    HTTP_BAD_REQUEST = 400,
    HTTP_UNAUTHORIZED = 401,
    HTTP_PAYMENT_REQUIRED = 402,
    HTTP_FORBIDDEN = 403,
    HTTP_NOT_FOUND = 404,
    HTTP_METHOD_NOT_ALLOWED = 405,
    HTTP_NOT_ACCEPTABLE = 406,
    HTTP_PROXY_AUTHENTICATION_REQUIRED = 407,
    HTTP_REQUEST_TIMEOUT = 408,
    HTTP_CONFLICT = 409,
    HTTP_GONE = 410,
    HTTP_LENGTH_REQUIRED = 411,
    HTTP_PRECONDITION_FAILED = 412,
    HTTP_REQUEST_ENTITY_TOO_LARGE = 413,
    HTTP_REQUEST_URI_TOO_LONG = 414,
    HTTP_UNSUPPORTED_MEDIA_TYPE = 415,
    HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416,
    HTTP_EXPECTATION_FAILED = 417,
    HTTP_I_AM_A_TEAPOT = 418,                                               // RFC2324
    HTTP_UNPROCESSABLE_ENTITY = 422,                                        // RFC4918
    HTTP_LOCKED = 423,                                                      // RFC4918
    HTTP_FAILED_DEPENDENCY = 424,                                           // RFC4918
    HTTP_RESERVED_FOR_WEBDAV_ADVANCED_COLLECTIONS_EXPIRED_PROPOSAL = 425,   // RFC2817
    HTTP_UPGRADE_REQUIRED = 426,                                            // RFC2817
    HTTP_PRECONDITION_REQUIRED = 428,                                       // RFC6585
    HTTP_TOO_MANY_REQUESTS = 429,                                           // RFC6585
    HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE = 431,                             // RFC6585
    HTTP_UNAVAILABLE_FOR_LEGAL_REASONS = 451,
    HTTP_INTERNAL_SERVER_ERROR = 500,
    HTTP_NOT_IMPLEMENTED = 501,
    HTTP_BAD_GATEWAY = 502,
    HTTP_SERVICE_UNAVAILABLE = 503,
    HTTP_GATEWAY_TIMEOUT = 504,
    HTTP_VERSION_NOT_SUPPORTED = 505,
    HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL = 506,                        // RFC2295
    HTTP_INSUFFICIENT_STORAGE = 507,                                        // RFC4918
    HTTP_LOOP_DETECTED = 508,                                               // RFC5842
    HTTP_NOT_EXTENDED = 510,                                                // RFC2774
    HTTP_NETWORK_AUTHENTICATION_REQUIRED = 511;                             // RFC6585

  protected const LABEL_SCRIPT_NONCE = '<script nonce="';

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
    if (!isset($baseParams['controller']))
    {
      if (isset($baseParams['route']) && $baseParams['route'] === 'otra_exception')
      {
        // Stores the bundle, module, controller and action for later use
        [$this->bundle, $this->module, $this->route, self::$hasCssToLoad, self::$hasJsToLoad] = array_values($baseParams);

        require CORE_PATH . 'services/securityService.php';
        $this->routeSecurityFilePath = CACHE_PATH . 'php/security/' . $this->route . '.php';
      }

      return;
    }

    // Stores the bundle, module, controller and action for later use
    [
      $this->pattern,
      $this->bundle,
      $this->module,
      $this->controller,
      ,
      $this->route,
      self::$hasJsToLoad,
      self::$hasCssToLoad] = array_values($baseParams);

    require CORE_PATH . 'services/securityService.php';
    $this->routeSecurityFilePath = CACHE_PATH . 'php/security/' .  $_SERVER[APP_ENV] . '/' . $this->route . '.php';

    if (!file_exists($this->routeSecurityFilePath))
      $this->routeSecurityFilePath = null;

    $this->action = substr($baseParams['action'], 0, -6);
    $this->getParams = $getParams;
    $mainPath = 'bundles/' . $this->bundle . '/' . $this->module . '/';
    // Stores the templates' path of the calling controller
    $this->viewPath = BASE_PATH . $mainPath . 'views/' . $this->controller . '/';
    $this->viewResourcePath = [
      'css' => '/' . $mainPath .'resources/css/',
      'js' => '/' . $mainPath . 'resources/js/'
    ];

    self::$path = $_SERVER['DOCUMENT_ROOT'] . '..';
    $this->url = $_SERVER['REQUEST_URI'];
  }

  /**
   * Encodes the value passed as parameter in order to create a cache file name
   *
   * @param string $route
   * @param string $path     File's path
   * @param string $suffix   Suffix of the file name
   * @param string $extension
   *
   * @return string The cache file name version of the file
   */
  #[Pure] protected static function getCacheFileName(
    string $route,
    string $path = CACHE_PATH,
    string $suffix = VERSION,
    string $extension = '.cache'
  ) : string {
    return $path . sha1('ca' . $route . $suffix . 'che') . $extension;
  }

  /**
   * If the file is in the cache and is "fresh" then gets it. WE HAVE TO HAVE AllConfig::$cache TO TRUE !!
   *
   * @param string $cachedFile The cache file name version of the file
   * @param bool   $exists     True if we know that the file exists.
   *
   * @throws \Exception
   * @return bool|string $content The cached (and cleaned) content if exists, false otherwise
   */
  protected static function getCachedFile(string $cachedFile, bool $exists = false) : bool|string
  {
    if (($exists || file_exists($cachedFile)) && (filemtime($cachedFile) + CACHE_TIME) > time())
      return preg_replace(
        [
          '@(<script.*?nonce=")\w{64}@',
          '@(<link.*?nonce=")\w{64}@',
        ],
        [
          '${1}' . getRandomNonceForCSP(),
          '${1}' . getRandomNonceForCSP('style-src')
        ],
        file_get_contents($cachedFile)
      );

    return false;
  }

  /**
   * Adds dynamically css script(s) (not coming from the routes configuration) to the existing ones.
   *
   * @param array|string $stylesheets The css file to add (Array of string)
   */
  protected static function css(array|string $stylesheets = []) : void
  {
    array_splice(
      self::$stylesheets,
      count(self::$stylesheets),
      0,
      is_array($stylesheets) ? $stylesheets : [$stylesheets]
    );
  }

  /**
   * Adds dynamically javascript script(s) (not coming from the routes configuration) to the existing ones.
   * If the keys are string it will add the string to the link.
   *
   * @param array|string $js The javascript file to add (Array of strings)
   */
  protected static function js(array|string $js = []) : void
  {
    self::$javaScript = array_merge(self::$javaScript, is_array($js) ? $js : [$js]);
  }

  /**
   * Use the template engine to render the final template. Fast if the blocks stack is not used.
   *
   * @param string $templateFilename
   * @param array  $variables
   *
   * @return string
   */
  protected static function processFinalTemplate(string $templateFilename, array $variables)
  {
    extract($variables);
    ob_start();
    require $templateFilename;

    // If the template motor is loaded then we use it
    return in_array(CORE_PATH . 'templating/blocks.php', get_included_files())
      ? BlocksSystem::getTemplate()
      : '';
  }

  /**
   * @param string $file     The file to render
   * @param bool   $viewPath If true, we adds the usual view path before the $file variable.
   *
   * @return array [$templateFile, $otraRoute]
   */
  #[Pure] protected function getTemplateFile(string $file, bool $viewPath) : array
  {
    $otraRoute = str_contains($this->route, 'otra_');

    if (!$otraRoute)
      $templateFile = $viewPath ? $this->viewPath . $file : $file;
    else
      $templateFile = CORE_VIEWS_PATH . $this->controller . '/' . $file;

    return [$templateFile, $otraRoute];
  }

  /**
   * @param string $content     The main content of the template
   * @param string $cssResource The css resources to link to the template
   * @param string $jsResource  The js resources to link to the template
   */
  protected static function addResourcesToTemplate(string &$content, string $cssResource, string $jsResource)
  {
    // the 'preg_replace' suppress useless spaces
    $content = preg_replace('/>\s+</', '><',
      !self::$ajax
        ? str_replace(
        self::OTRA_LABEL_ENDING_TITLE_TAG,
        self::OTRA_LABEL_ENDING_TITLE_TAG . $cssResource,
        $content . $jsResource)
        : $cssResource . $content . $jsResource
    );
  }

  /**
   * If the files are in cache, put them directly in $rendered
   *
   * @param array $filesToCheck Files to check in cache
   *
   * @throws \Exception
   * @return bool True if ALL the files are in cache, false otherwise
   */
  public function checkCache(array $filesToCheck) : bool
  {
    foreach($filesToCheck as $fileToCheck)
    {
      $templateFile = $this->viewPath . $fileToCheck;
      $cachedFile = self::getCacheFileName($this->route);

      if (!file_exists($cachedFile))
        return false;

      self::$rendered[$templateFile] = self::getCachedFile($cachedFile, true);

      if (false === self::$rendered[$templateFile])
        return false;
    }

    return true;
  }

  /**
   * @param string $templateFile
   * @param array  $variables
   * @param bool   $ajax
   * @param string $route
   * @param array  $viewResourcePath Paths to CSS and JS files
   *
   * @throws \Exception
   */
  protected static function handleCache(
    string $templateFile,
    array $variables,
    bool $ajax,
    string $route,
    array $viewResourcePath)
  {
    if ($ajax)
      self::$ajax = $ajax;

    self::$cacheUsed = isset(self::$rendered[$templateFile]);

    // If we already have the file in 'cache memory' then we serve it
    if (self::$cacheUsed)
      self::$template = self::$rendered[$templateFile];
    else // otherwise if we have the file in a 'cache file' then we serve it, otherwise we build the 'cache file'
    {
      $cachedFile = self::getCacheFileName($route);
      self::$template = (false === self::getCachedFile($cachedFile)
        || (property_exists(AllConfig::class, 'cache') && !AllConfig::$cache))
        ? self::buildCachedFile($templateFile, $variables, $route, $cachedFile, $viewResourcePath)
        : self::getCachedFile($cachedFile, true);
    }
  }

  /**
   * Parses the template file and updates parent::$template
   *
   * @param string      $templateFile     The file name
   * @param array       $variables        Variables to pass to the template
   * @param string      $route
   * @param string|null $cachedFile       The cache file name version of the file
   * @param array       $viewResourcePath
   *
   * @throws Exception|OtraException
   * @return string
   */
  protected static function buildCachedFile(
    string $templateFile,
    array $variables,
    string $route,
    string $cachedFile = null,
    array $viewResourcePath = []) : string
  {
    $content = MasterController::processFinalTemplate($templateFile, $variables);
    [$cssResource, $jsResource] = static::getTemplateResources($route, $viewResourcePath);
    self::addResourcesToTemplate($content, $cssResource, $jsResource);

    // We clear these variables in order to put css and js for other modules that will not be cached (in case there are
    // css and js imported in the layout)
    self::$javaScript = self::$stylesheets = [];

    if ('cli' === PHP_SAPI)
      return $content;

    // If the cached filename is specified and if the cache is activated, we create a cached file.
    if (null !== $cachedFile
      && (!property_exists(AllConfig::class, 'cache') || AllConfig::$cache))
    {
      if (file_put_contents($cachedFile, $content) === false && $route !== 'otra_exception')
        throw new OtraException('We cannot create/update the cache for the route \'' . $route . '\'.' .
          PHP_EOL . 'This file is \'' . $cachedFile. '\'.');
    }

    return $content;
  }
}

// We handle the edge case of the blocks.php file that is included via a template and needs MasterController,
// allowing the block.php file of the template engine system to work in production mode,
// by creating a class alias. Disabled when passing via the command line tasks.
if ($_SERVER[APP_ENV] === 'prod' && PHP_SAPI !== 'cli')
  class_alias('\cache\php\MasterController', '\otra\MasterController');
