<?php
declare(strict_types=1);
/** MVC master controller class
 *
 * @author Lionel Péramo
 */
namespace otra;

use cache\php\BlocksSystem;

/**
 * @package otra
 */
class MasterController
{
  public static string $path;
  public ?string $routeSecurityFilePath = null;

  public static array
    $nonces = [
      'script-src' => [],
      'style-src' => []
    ],
    $contentSecurityPolicy = [
    'dev' =>
      [
        'frame-ancestors' => "'self'",
        'default-src' => "'self'",
        'font-src' => "'self'",
        'img-src' => "'self'",
        'object-src' => "'self'",
        'connect-src' => "'self'",
        'child-src' => "'self'",
        'manifest-src' => "'self'",
        'style-src' => "'self'"
      ],
    'prod' => [] // assigned in the constructor
  ],
    $featurePolicy = [
    'dev' =>
      [
        'layout-animations' => "'self'",
        'legacy-image-formats' => "'none'",
        'oversized-images' => "'none'",
        'sync-script' => "'none'",
        'sync-xhr' => "'none'",
        'unoptimized-images' => "'none'",
        'unsized-media' => "'none'"
      ],
    'prod' => []
  ],
  $routesSecurity;

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
    $css = [],
    $javaScript = [],
    $rendered = [];

  protected static bool
    $ajax = false,
    $hasJsToLoad,
    $hasCssToLoad;

  protected static string
    $id,
    $layout;

  /* @var string $template The actual template being processed */
  protected static $template;

  // HTTP codes !
  public const HTTP_CONTINUE = 100;
  public const HTTP_SWITCHING_PROTOCOLS = 101;
  public const HTTP_PROCESSING = 102;            // RFC2518
  public const HTTP_OK = 200;
  public const HTTP_CREATED = 201;
  public const HTTP_ACCEPTED = 202;
  public const HTTP_NON_AUTHORITATIVE_INFORMATION = 203;
  public const HTTP_NO_CONTENT = 204;
  public const HTTP_RESET_CONTENT = 205;
  public const HTTP_PARTIAL_CONTENT = 206;
  public const HTTP_MULTI_STATUS = 207;          // RFC4918
  public const HTTP_ALREADY_REPORTED = 208;      // RFC5842
  public const HTTP_IM_USED = 226;               // RFC3229
  public const HTTP_MULTIPLE_CHOICES = 300;
  public const HTTP_MOVED_PERMANENTLY = 301;
  public const HTTP_FOUND = 302;
  public const HTTP_SEE_OTHER = 303;
  public const HTTP_NOT_MODIFIED = 304;
  public const HTTP_USE_PROXY = 305;
  public const HTTP_RESERVED = 306;
  public const HTTP_TEMPORARY_REDIRECT = 307;
  public const HTTP_PERMANENTLY_REDIRECT = 308;  // RFC7238
  public const HTTP_BAD_REQUEST = 400;
  public const HTTP_UNAUTHORIZED = 401;
  public const HTTP_PAYMENT_REQUIRED = 402;
  public const HTTP_FORBIDDEN = 403;
  public const HTTP_NOT_FOUND = 404;
  public const HTTP_METHOD_NOT_ALLOWED = 405;
  public const HTTP_NOT_ACCEPTABLE = 406;
  public const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
  public const HTTP_REQUEST_TIMEOUT = 408;
  public const HTTP_CONFLICT = 409;
  public const HTTP_GONE = 410;
  public const HTTP_LENGTH_REQUIRED = 411;
  public const HTTP_PRECONDITION_FAILED = 412;
  public const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
  public const HTTP_REQUEST_URI_TOO_LONG = 414;
  public const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
  public const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
  public const HTTP_EXPECTATION_FAILED = 417;
  public const HTTP_I_AM_A_TEAPOT = 418;                                               // RFC2324
  public const HTTP_UNPROCESSABLE_ENTITY = 422;                                        // RFC4918
  public const HTTP_LOCKED = 423;                                                      // RFC4918
  public const HTTP_FAILED_DEPENDENCY = 424;                                           // RFC4918
  public const HTTP_RESERVED_FOR_WEBDAV_ADVANCED_COLLECTIONS_EXPIRED_PROPOSAL = 425;   // RFC2817
  public const HTTP_UPGRADE_REQUIRED = 426;                                            // RFC2817
  public const HTTP_PRECONDITION_REQUIRED = 428;                                       // RFC6585
  public const HTTP_TOO_MANY_REQUESTS = 429;                                           // RFC6585
  public const HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;                             // RFC6585
  public const HTTP_UNAVAILABLE_FOR_LEGAL_REASONS = 451;
  public const HTTP_INTERNAL_SERVER_ERROR = 500;
  public const HTTP_NOT_IMPLEMENTED = 501;
  public const HTTP_BAD_GATEWAY = 502;
  public const HTTP_SERVICE_UNAVAILABLE = 503;
  public const HTTP_GATEWAY_TIMEOUT = 504;
  public const HTTP_VERSION_NOT_SUPPORTED = 505;
  public const HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL = 506;                        // RFC2295
  public const HTTP_INSUFFICIENT_STORAGE = 507;                                        // RFC4918
  public const HTTP_LOOP_DETECTED = 508;                                               // RFC5842
  public const HTTP_NOT_EXTENDED = 510;                                                // RFC2774
  public const HTTP_NETWORK_AUTHENTICATION_REQUIRED = 511;                             // RFC6585

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
    if (false === isset($baseParams['controller']))
    {
      if (isset($baseParams['route']) === true && $baseParams['route'] === 'otra_exception')
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

    self::$id = $this->bundle . $this->module . $this->controller . $this->action;
    $this->getParams = $getParams;

    $mainPath = 'bundles/' . $this->bundle . '/' . $this->module . '/';
    // Stores the templates' path of the calling controller
    $this->viewPath = BASE_PATH . $mainPath . 'views/' . $this->controller . '/';
    $this->viewResourcePath = [
      'css' => '/' . $mainPath .'resources/css/',
      'js' => '/' . $mainPath . 'resources/js/'
    ];

    self::$path = $_SERVER['DOCUMENT_ROOT'] . '..';
    self::$contentSecurityPolicy['prod'] = self::$contentSecurityPolicy['dev'];
    $this->url = $_SERVER['REQUEST_URI'];
  }

  /**
   * Encodes the value passed as parameter in order to create a cache file name
   *
   * @param string $filename File name to modify
   * @param string $path File's path
   * @param string $prefix Prefix of the file name
   * @param string $extension
   *
   * @return string The cache file name version of the file
   */
  protected static function getCacheFileName(string $filename, string $path = CACHE_PATH, string $prefix = VERSION, string $extension = '.cache') : string {
    return $path . sha1('ca' . $prefix . $filename . 'che') . $extension;
  }

  /**
   * If the file is in the cache and is "fresh" then gets it. WE HAVE TO HAVE AllConfig::$cache TO TRUE !!
   *
   * @param string $cachedFile The cache file name version of the file
   * @param bool   $exists     True if we know that the file exists.
   *
   * @return string|bool $content The cached (and cleaned) content if exists, false otherwise
   */
  protected static function getCachedFile(string $cachedFile, bool $exists = false)
  {
    if ((true === $exists || true === file_exists($cachedFile)) && (filemtime($cachedFile) + CACHE_TIME) > time())
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
   * @param array $css The css file to add (Array of string)
   */
  protected static function css($css = []) : void
  {
    array_splice(self::$css, count(self::$css), 0, (is_array($css)) ? $css : [$css]);
  }

  /**
   * Adds dynamically javascript script(s) (not coming from the routes configuration) to the existing ones.
   * If the keys are string it will add the string to the link.
   *
   * @param array $js The javascript file to add (Array of strings)
   */
  protected static function js($js = []) : void
  {
    self::$javaScript = array_merge(self::$javaScript, (is_array($js)) ? $js : [$js]);
  }

  /**
   * Use the template engine to render the final template. Fast if the blocks stack is not used.
   *
   * @param string $templateFilename
   * @param array  $variables
   *
   * @return string
   */
  protected static function processFinalTemplate(string &$templateFilename, array &$variables)
  {
    extract($variables);
    ob_start();
    require $templateFilename;

//    require CORE_PATH . 'templating/visualRendering.php';
//    showBlocksVisually();
//    die;

    // If the template motor is loaded then we use it
    return in_array(CORE_PATH . 'templating/blocks.php', get_included_files())
      ? BlocksSystem::getTemplate()
      : '';
  }
}

// We handle the edge case of the blocks.php file that is included via a template and needs MasterController,
// allowing the block.php file of the template engine system to work in production mode,
// by creating a class alias. Disabled when passing via the command line tasks.
if ($_SERVER[APP_ENV] === 'prod' && PHP_SAPI !== 'cli')
  class_alias('\cache\php\MasterController', '\otra\MasterController');
