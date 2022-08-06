<?php
/** MVC master controller class
 *
 * @author Lionel Péramo
 */
declare(strict_types=1);

namespace otra;

use otra\cache\php\BlocksSystem;
use otra\config\AllConfig;
use Exception;
use JetBrains\PhpStorm\Pure;
use ReflectionException;
use const otra\cache\php\
{APP_ENV, BASE_PATH, CACHE_PATH, CORE_PATH, CORE_VIEWS_PATH, DEV, DIR_SEPARATOR, PROD, VERSION};
use function otra\services\getRandomNonceForCSP;
use function otra\templating\showBlocksVisually;
use const otra\services\OTRA_KEY_STYLE_SRC_DIRECTIVE;

/**
 * @author Lionel Péramo
 * @package otra
 */
abstract class MasterController
{
  public static array $nonces = [
    'script-src' => [],
    'style-src' => []
  ];

  public static string
    $cacheUsed = 'Unused',
    $path;

  public ?string $routeSecurityFilePath = null;
  public string
    $bundle = '',
    $module = '',
    $route,
    $viewPath = DIR_SEPARATOR; // index/index/ for indexController and indexAction

  protected string
    $action = '',
    $controller = '',
    $pattern = '', // path to the action, e.g. "application/bundle/controller/action" => "HelloWorld/frontend/index/Home
    $response;

  protected array
    $params = [],
    $viewResourcePath = [
      'css' => DIR_SEPARATOR, // CSS path for this module
      'js' => DIR_SEPARATOR  // JS path for this module
    ];

  protected static array
    $javaScripts = [],
    /** @var array<string,string> */
    $rendered = [],
    $stylesheets = [];

  protected static bool
    $ajax = false,
    $hasCssToLoad,
    $hasJsToLoad;

  protected static string $layout;

  /* @var bool|string $template The actual template being processed */
  protected static bool|string|null $template;

  // Those two static variables are constants in fact, but we have to maintain the naming norm
  private static int
    $stylesheetFile = 0,
    $printStylesheet = 1;

  final public const
    OTRA_LABEL_ENDING_TITLE_TAG = '/title>',
    HTTP_CODES =
    [
      'HTTP_CONTINUE' => 100,
      'HTTP_SWITCHING_PROTOCOLS' => 101,
      'HTTP_PROCESSING' => 102,            // RFC2518
      'HTTP_OK' => 200,
      'HTTP_CREATED' => 201,
      'HTTP_ACCEPTED' => 202,
      'HTTP_NON_AUTHORITATIVE_INFORMATION' => 203,
      'HTTP_NO_CONTENT' => 204,
      'HTTP_RESET_CONTENT' => 205,
      'HTTP_PARTIAL_CONTENT' => 206,
      'HTTP_MULTI_STATUS' => 207,          // RFC4918
      'HTTP_ALREADY_REPORTED' => 208,      // RFC5842
      'HTTP_IM_USED' => 226,               // RFC3229
      'HTTP_MULTIPLE_CHOICES' => 300,
      'HTTP_MOVED_PERMANENTLY' => 301,
      'HTTP_FOUND' => 302,
      'HTTP_SEE_OTHER' => 303,
      'HTTP_NOT_MODIFIED' => 304,
      'HTTP_USE_PROXY' => 305,
      'HTTP_RESERVED' => 306,
      'HTTP_TEMPORARY_REDIRECT' => 307,
      'HTTP_PERMANENTLY_REDIRECT' => 308,  // RFC7238
      'HTTP_BAD_REQUEST' => 400,
      'HTTP_UNAUTHORIZED' => 401,
      'HTTP_PAYMENT_REQUIRED' => 402,
      'HTTP_FORBIDDEN' => 403,
      'HTTP_NOT_FOUND' => 404,
      'HTTP_METHOD_NOT_ALLOWED' => 405,
      'HTTP_NOT_ACCEPTABLE' => 406,
      'HTTP_PROXY_AUTHENTICATION_REQUIRED' => 407,
      'HTTP_REQUEST_TIMEOUT' => 408,
      'HTTP_CONFLICT' => 409,
      'HTTP_GONE' => 410,
      'HTTP_LENGTH_REQUIRED' => 411,
      'HTTP_PRECONDITION_FAILED' => 412,
      'HTTP_REQUEST_ENTITY_TOO_LARGE' => 413,
      'HTTP_REQUEST_URI_TOO_LONG' => 414,
      'HTTP_UNSUPPORTED_MEDIA_TYPE' => 415,
      'HTTP_REQUESTED_RANGE_NOT_SATISFIABLE' => 416,
      'HTTP_EXPECTATION_FAILED' => 417,
      'HTTP_I_AM_A_TEAPOT' => 418,                                               // RFC2324
      'HTTP_UNPROCESSABLE_ENTITY' => 422,                                        // RFC4918
      'HTTP_LOCKED' => 423,                                                      // RFC4918
      'HTTP_FAILED_DEPENDENCY' => 424,                                           // RFC4918
      'HTTP_RESERVED_FOR_WEBDAV_ADVANCED_COLLECTIONS_EXPIRED_PROPOSAL' => 425,   // RFC2817
      'HTTP_UPGRADE_REQUIRED' => 426,                                            // RFC2817
      'HTTP_PRECONDITION_REQUIRED' => 428,                                       // RFC6585
      'HTTP_TOO_MANY_REQUESTS' => 429,                                           // RFC6585
      'HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE' => 431,                             // RFC6585
      'HTTP_UNAVAILABLE_FOR_LEGAL_REASONS' => 451,
      'HTTP_INTERNAL_SERVER_ERROR' => 500,
      'HTTP_NOT_IMPLEMENTED' => 501,
      'HTTP_BAD_GATEWAY' => 502,
      'HTTP_SERVICE_UNAVAILABLE' => 503,
      'HTTP_GATEWAY_TIMEOUT' => 504,
      'HTTP_VERSION_NOT_SUPPORTED' => 505,
      'HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL' => 506,                        // RFC2295
      'HTTP_INSUFFICIENT_STORAGE' => 507,                                        // RFC4918
      'HTTP_LOOP_DETECTED' => 508,                                               // RFC5842
      'HTTP_NOT_EXTENDED' => 510,                                                // RFC2774
      'HTTP_NETWORK_AUTHENTICATION_REQUIRED' => 511                              // RFC6585
    ];

  protected const
    CSS_MEDIA_SCREEN = 0,
    LABEL_SCRIPT_NONCE = '<script nonce="';

  /**
   * @param array{
   *  pattern?: string,
   *  bundle?: string,
   *  module?: string,
   *  controller?: string,
   *  action?: string,
   *  route?: string,
   *  js: bool,
   *  css: bool
   * } $otraParams
   *
   * @param array $params The params passed by GET method
   */
  public function __construct(array $otraParams = [], array $params = [])
  {
    // If a controller is specified (in the other case, the calling controller is the Bootstrap class)
    if (!isset($otraParams['controller']))
    {
      if (isset($otraParams['route']) && $otraParams['route'] === 'otra_exception')
      {
        // Stores the bundle, module, controller and action for later use
        [
          $this->bundle,
          $this->module,
          $this->route,
          self::$hasCssToLoad,
          self::$hasJsToLoad
        ] = array_values($otraParams);

        // require_once needed, instead of require, because of the RouterTest::testGet_Launch test
        require_once CORE_PATH . 'services/securityService.php';
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
      self::$hasCssToLoad
    ] = array_values($otraParams);

    // require_once needed, instead of require, because of the RouterTest::testGet_Launch test
    require_once CORE_PATH . 'services/securityService.php';

    $this->routeSecurityFilePath = CACHE_PATH . 'php/security/' .  $_SERVER[APP_ENV] . DIR_SEPARATOR . $this->route .
      '.php';

    if (!file_exists($this->routeSecurityFilePath))
      $this->routeSecurityFilePath = null;

    $this->action = substr($otraParams['action'], 0, -6);
    $this->params = $params;
    $mainPath = 'bundles/' . $this->bundle . DIR_SEPARATOR . $this->module . DIR_SEPARATOR;
    // Stores the templates' path of the calling controller
    $this->viewPath = BASE_PATH . $mainPath . 'views/' . $this->controller . DIR_SEPARATOR;
    $this->viewResourcePath = [
      'css' => DIR_SEPARATOR . $mainPath . 'resources/css/',
      'js' => DIR_SEPARATOR . $mainPath . 'resources/js/'
    ];

    self::$path = $_SERVER['DOCUMENT_ROOT'] . '..';
  }

  /**
   * Encodes the value passed as parameter in order to create a cache file name
   *
   * @param string $path     File's path
   * @param string $suffix   Suffix of the file name
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
   * If the file is in the cache and is "fresh" then gets it.
   *
   * @param string $cachedFile The cache file name version of the file
   *
   * @throws Exception
   * @return bool|string $content The cached (and cleaned) content if exists, false otherwise
   */
  protected static function getCachedFileContent(string $cachedFile) : bool|string
  {
    return (!file_exists($cachedFile) || filemtime($cachedFile) + CACHE_TIME <= time())
      ? false
      : preg_replace(
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
  }

  /**
   * Adds dynamically css script(s) (not coming from the routes' configuration) to the existing ones.
   *
   * @param array $stylesheets The css files to add [0 => File, 1 => Print]
   *                           Do not put '.css'.
   *                           /!\ DO NOT fill the second key if it is not needed
   * @param bool  $print       Does the stylesheet must be only used for a print usage ?
   */
  public static function css(array $stylesheets = [], bool $print = false) : void
  {
    array_push(
      self::$stylesheets,
      ...$stylesheets
    );
  }

  /**
   * Adds dynamically javascript script(s) (not coming from the routes' configuration) to the existing ones.
   * If the keys are string it will add the string to the link.
   *
   * @param array|string $js The javascript file to add (Array of strings)
   */
  public static function js(array|string $js = []) : void
  {
    self::$javaScripts = array_merge(self::$javaScripts, is_array($js) ? $js : [$js]);
  }

  /**
   * Use the template engine to render the final template. Fast if the blocks stack is not used.
   *
   *
   * @throws OtraException|ReflectionException
   * @return string
   */
  protected static function processFinalTemplate(string $route, string $templateFilename, array $variables) : string
  {
    extract($variables);
    ob_start();
    require $templateFilename;

    // If the template motor is not loaded then we quit
    if (!in_array(CORE_PATH . 'templating/blocks.php', get_included_files()))
    {
      // closes the output buffering and return the content as is
      return ob_get_clean();
    }

    // Puts the motor template visualization system into session
    // if we are in a development environment
    // AND if we do not look an OTRA route
    if ($_SERVER[APP_ENV] === DEV && !str_contains($route, 'otra_'))
    {
      if (!in_array(CORE_PATH . 'templating/blocks.php', get_included_files()))
        return '';
      else
      {
        ob_start();

        require CORE_PATH . 'views/profiler/templateStructure/visualRendering.php';
        ob_clean();
        showBlocksVisually(false);
        Session::init();
        Session::set('templateVisualization', base64_encode(ob_get_clean()));
      }
    }

    // If the template motor is loaded then we use it
    return BlocksSystem::getTemplate();
  }

  /**
   * @param string $file     The file to render
   * @param bool   $viewPath If true, we add the usual view path before the `$file` variable.
   *
   * @return array{0:string,1:string} [$templateFile, $otraRoute]
   */
  protected function getTemplateFile(string $file, bool $viewPath) : array
  {
    if (!str_contains($this->route, 'otra_'))
      return [
        ($viewPath ? $this->viewPath : '') . $file,
        false
      ];

    return [
      CORE_VIEWS_PATH . $this->controller . DIR_SEPARATOR . $file,
      true
    ];
  }

  /**
   * @param string $content     The main content of the template
   * @param string $cssResource The css resources to link to the template
   * @param string $jsResource  The js resources to link to the template
   */
  protected static function addResourcesToTemplate(string &$content, string $cssResource, string $jsResource) : void
  {
    // We add the JavaScript just before the ending body tag
    $contentAndJs = str_replace(
      '</body',
      $jsResource . '</body',
      $content
    );

    // adding CSS after the title tag or just before the content if we use AJAX
    $content = self::$ajax
      ? $cssResource . $contentAndJs
      : str_replace(
        self::OTRA_LABEL_ENDING_TITLE_TAG,
        self::OTRA_LABEL_ENDING_TITLE_TAG . $cssResource,
        $contentAndJs
      );
  }

  /**
   * @param array  $viewResourcePath Paths to CSS and JS files
   *
   * @throws Exception
   */
  protected static function handleCache(
    string $templateFile,
    array $variables,
    bool $ajax,
    string $route,
    array $viewResourcePath) : void
  {
    // Is the cache activated?
    $cacheActivated = property_exists(AllConfig::class, 'cache') && AllConfig::$cache;

    if ($cacheActivated)
    {
      // cacheUsed is used in order to simplify conditions and show a status on the debug bar
      if (isset(self::$rendered[$templateFile]))
        self::$cacheUsed = 'memory';

      if (self::$cacheUsed === 'memory')
        self::$template = self::$rendered[$templateFile];
      else // otherwise, if we have the file in a .cache file then we serve it, otherwise we build the 'cache file'
      {
        $cachedFile = self::getCacheFileName($route);
        $cachedFileContent = self::getCachedFileContent($cachedFile);

        // There is no .cache file for this template, so we render it and store it in a file
        if (false === $cachedFileContent)
        {
          // Will be used in 'addResourcesToTemplate' method via 'render' method
          if ($ajax)
            self::$ajax = true;

          self::$template = self::render($templateFile, $variables, $route, $viewResourcePath);

          if (file_put_contents($cachedFile, self::$template) === false && $route !== 'otra_exception')
            throw new OtraException('We cannot create/update the cache for the route \'' . $route . '\'.' .
              PHP_EOL . 'This file is \'' . $cachedFile. '\'.');
        } else // otherwise, we just get it
        {
          self::$template = $cachedFileContent;
          self::$cacheUsed = '.cache file';
        }

        // We store the freshly rendered template into memory
        self::$rendered[$templateFile] = self::$template;
      }
    } else // cache is not activated
    {
      // Will be used in 'addResourcesToTemplate' method via 'render' method
      if ($ajax)
        self::$ajax = true;

      self::$template = self::render($templateFile, $variables, $route, $viewResourcePath);
    }
  }

  /**
   * Parses the template file and updates parent::$template
   *
   * @param string $templateFile The file name
   * @param array  $variables    Variables to pass to the template
   *
   * @throws OtraException
   * @throws ReflectionException
   * @throws Exception
   * @return string
   */
  protected static function render(
    string $templateFile,
    array $variables,
    string $route,
    array $viewResourcePath = []) : string
  {
    $content = MasterController::processFinalTemplate($route, $templateFile, $variables);
    [$cssResource, $jsResource] = static::getTemplateResources($route, $viewResourcePath);

    if (self::$ajax)
    {
      $titlePosition = mb_strrpos($content, '</title>');
      $cssContent = $cssResource . self::addDynamicCSS();
      $tempContent = (false !== $titlePosition)
        ? substr_replace($content, $cssContent, $titlePosition + 8, 0) // 8 = strlen('</title>')
        : $cssContent . $content;
      $bodyPosition = mb_strrpos($tempContent, '</body>');

      $content = (false !== $bodyPosition)
        ? substr_replace($tempContent, $jsResource, $bodyPosition, 0)
        : $tempContent . $jsResource;
    }
    else
    {
      $content = str_replace(
        self::OTRA_LABEL_ENDING_TITLE_TAG,
        self::OTRA_LABEL_ENDING_TITLE_TAG . self::addDynamicCSS(),
        str_replace(
          '</body>',
          self::addDynamicJS() . '</body>',
          $content
        )
      );
      self::addResourcesToTemplate($content, $cssResource, $jsResource);
    }

    // the 'preg_replace' suppress useless spaces
    $content = preg_replace('/>\s+</', '><', $content);

    // We clear these variables in order to put css and js for other modules that will not be cached (in case there are
    // css and js imported in the layout)
    self::$javaScripts = self::$stylesheets = [];

    return $content;
  }

  /**
   * Adds extra CSS dynamically (needed for the debug bar for example).
   *
   * @throws Exception
   * @return string
   */
  public static function addDynamicCSS() : string
  {
    $cssContent = '';

    foreach(self::$stylesheets as $stylesheet)
    {
      $cssContent .= PHP_EOL . '<link rel="stylesheet" nonce="' .
        getRandomNonceForCSP(OTRA_KEY_STYLE_SRC_DIRECTIVE) . '" href="' . $stylesheet[self::$stylesheetFile] .
        '.css" media="' . (isset($stylesheet[self::$printStylesheet]) && $stylesheet[self::$printStylesheet]
          ? 'print'
          : 'screen')
        . '"/>';
    }

    return $cssContent;
  }

  /**
   * Adds extra JS dynamically (needed for the debug bar for example).
   *
   * @throws Exception
   * @return string
   */
  public static function addDynamicJS() : string
  {
    $jsContent = '';

    foreach(self::$javaScripts as $javaScript)
    {
      $jsContent .= self::LABEL_SCRIPT_NONCE .
        getRandomNonceForCSP() . '" src="' . $javaScript . '.js" ></script>';
    }

    return $jsContent;
  }

  /**
   * Gets AJAX CSS. Needed because if we put CSS in the body, it will be replaced on some page change.
   * We then need to put the AJAX CSS to the head as the head is never replaced.
   *
   * @throws Exception
   * @return array
   */
  public static function getAjaxCSS() : array
  {
    $cssContent = [];

    foreach(self::$stylesheets as $stylesheetType => $stylesheets)
    {
      foreach($stylesheets as $stylesheet)
      {
        $cssContent[] = [
          'href' => $stylesheet . '.css',
          'nonce' => getRandomNonceForCSP(),
          'media' => ($stylesheetType === self::CSS_MEDIA_SCREEN)
            ? 'screen'
            : 'print'
        ];
      }
    }

    return $cssContent;
  }

  /**
   * Gets AJAX JS
   *
   * @throws Exception
   * @return array
   */
  public static function getAjaxJS() : array
  {
    $jsContent = [];

    foreach(self::$javaScripts as $javaScript)
    {
      $jsContent[] = [
        'nonce' => getRandomNonceForCSP(),
        'src' => $javaScript . '.js'
      ];
    }

    return $jsContent;
  }
}

// We handle the edge case of the blocks.php file that is included via a template and needs MasterController,
// allowing the block.php file of the template engine system to work in production mode,
// by creating a class alias. Disabled when passing via the command line tasks.
if ($_SERVER[APP_ENV] === PROD && PHP_SAPI !== 'cli')
  class_alias('otra\cache\php\MasterController', '\otra\MasterController');
