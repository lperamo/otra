<?php
/** MVC master controller class
 *
 * @author Lionel Péramo
 */
namespace lib\myLibs;

class MasterController
{
  public static string $path;
  public static array
    $blocksStack = [],
    $currentBlock = [
      'content' => '',
      'index' => 0,
      'name' => 'root',
      'parent' => null
    ],
    $blockNames = [],
    $blocksToUnset = [];

  public static int
    $currentBlocksStackIndex = 0;

  public string $route,
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
    $js = [],
    $rendered = [];

  protected static bool
    $ajax = false,
    $hasJsToLoad,
    $hasCssToLoad;

  protected static string
    $id,
  /* @var string $template The actual template being processed */
    $template,
    $layout;

  // HTTP codes !
  const HTTP_CONTINUE = 100;
  const HTTP_SWITCHING_PROTOCOLS = 101;
  const HTTP_PROCESSING = 102;            // RFC2518
  const HTTP_OK = 200;
  const HTTP_CREATED = 201;
  const HTTP_ACCEPTED = 202;
  const HTTP_NON_AUTHORITATIVE_INFORMATION = 203;
  const HTTP_NO_CONTENT = 204;
  const HTTP_RESET_CONTENT = 205;
  const HTTP_PARTIAL_CONTENT = 206;
  const HTTP_MULTI_STATUS = 207;          // RFC4918
  const HTTP_ALREADY_REPORTED = 208;      // RFC5842
  const HTTP_IM_USED = 226;               // RFC3229
  const HTTP_MULTIPLE_CHOICES = 300;
  const HTTP_MOVED_PERMANENTLY = 301;
  const HTTP_FOUND = 302;
  const HTTP_SEE_OTHER = 303;
  const HTTP_NOT_MODIFIED = 304;
  const HTTP_USE_PROXY = 305;
  const HTTP_RESERVED = 306;
  const HTTP_TEMPORARY_REDIRECT = 307;
  const HTTP_PERMANENTLY_REDIRECT = 308;  // RFC7238
  const HTTP_BAD_REQUEST = 400;
  const HTTP_UNAUTHORIZED = 401;
  const HTTP_PAYMENT_REQUIRED = 402;
  const HTTP_FORBIDDEN = 403;
  const HTTP_NOT_FOUND = 404;
  const HTTP_METHOD_NOT_ALLOWED = 405;
  const HTTP_NOT_ACCEPTABLE = 406;
  const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
  const HTTP_REQUEST_TIMEOUT = 408;
  const HTTP_CONFLICT = 409;
  const HTTP_GONE = 410;
  const HTTP_LENGTH_REQUIRED = 411;
  const HTTP_PRECONDITION_FAILED = 412;
  const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
  const HTTP_REQUEST_URI_TOO_LONG = 414;
  const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
  const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
  const HTTP_EXPECTATION_FAILED = 417;
  const HTTP_I_AM_A_TEAPOT = 418;                                               // RFC2324
  const HTTP_UNPROCESSABLE_ENTITY = 422;                                        // RFC4918
  const HTTP_LOCKED = 423;                                                      // RFC4918
  const HTTP_FAILED_DEPENDENCY = 424;                                           // RFC4918
  const HTTP_RESERVED_FOR_WEBDAV_ADVANCED_COLLECTIONS_EXPIRED_PROPOSAL = 425;   // RFC2817
  const HTTP_UPGRADE_REQUIRED = 426;                                            // RFC2817
  const HTTP_PRECONDITION_REQUIRED = 428;                                       // RFC6585
  const HTTP_TOO_MANY_REQUESTS = 429;                                           // RFC6585
  const HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;                             // RFC6585
  const HTTP_UNAVAILABLE_FOR_LEGAL_REASONS = 451;
  const HTTP_INTERNAL_SERVER_ERROR = 500;
  const HTTP_NOT_IMPLEMENTED = 501;
  const HTTP_BAD_GATEWAY = 502;
  const HTTP_SERVICE_UNAVAILABLE = 503;
  const HTTP_GATEWAY_TIMEOUT = 504;
  const HTTP_VERSION_NOT_SUPPORTED = 505;
  const HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL = 506;                        // RFC2295
  const HTTP_INSUFFICIENT_STORAGE = 507;                                        // RFC4918
  const HTTP_LOOP_DETECTED = 508;                                               // RFC5842
  const HTTP_NOT_EXTENDED = 510;                                                // RFC2774
  const HTTP_NETWORK_AUTHENTICATION_REQUIRED = 511;                             // RFC6585

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
      if (isset($baseParams['route']) === true && $baseParams['route'] === 'exception')
        // Stores the bundle, module, controller and action for later use
        list($this->bundle, $this->module, $this->route) = array_values($baseParams);

      return;
    }

    // Stores the bundle, module, controller and action for later use
    list($this->pattern, $this->bundle, $this->module, $this->controller, , $this->route, self::$hasJsToLoad, self::$hasCssToLoad) = array_values($baseParams);

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

    call_user_func_array([$this, $baseParams['action']], $getParams);
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
  protected static function getCacheFileName(string $filename, string $path = CACHE_PATH, string $prefix = '', string $extension = '.cache') : string {
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
      return file_get_contents ($cachedFile);

    return false;
  }

  /**
   * Adds a css script to the existing ones
   *
   * @param array $css The css file to add (Array of string)
   */
  protected static function css($css = []) : void
  {
    array_splice(self::$css, count(self::$css), 0, (is_array($css)) ? $css : [$css]);
  }

  /**
   * Adds one or more javascript scripts to the existing ones. If the keys are string it will add the string to the link.
   *
   * @param array $js The javascript file to add (Array of strings)
   */
  protected static function js($js = []) : void
  {
    self::$js = array_merge(self::$js, (is_array($js)) ? $js : [$js]);
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
    self::$currentBlock['content'] .= ob_get_clean();
    array_push(self::$blocksStack, self::$currentBlock);
    $content = '';
    $indexesToUnset = [];

    // Loops through the block stack to compile the final content that have to be shown
    foreach(self::$blocksStack as $key => &$block)
    {
      $blockExists = array_key_exists($block['name'], self::$blockNames);

      // If there are other blocks with this name...
      if ($blockExists === true)
      {
        $goodBlock = &$block;

        // We seeks for the last block with this name and we adds its content
        while(array_key_exists('replacedBy', $goodBlock) === true)
        {
          $goodBlock['content'] = '';
          $indexesToUnset[$goodBlock['index']] = true;
          $tmpKey = $key;
          $tmpBlock = &self::$blocksStack[$tmpKey + 1];

          while ($tmpBlock['parent'] === self::$blocksStack[$tmpKey] && $tmpBlock['name'] !== $block['name'])
          {
            $tmpBlock['content'] = '';
            $indexesToUnset[$tmpBlock['index']] = true;
            $tmpBlock = &self::$blocksStack[++$tmpKey + 1];
          }

          $goodBlock = &self::$blocksStack[$goodBlock['replacedBy']];
        }

        // We must also not show the endings blocks that have been replaced
        if (in_array($goodBlock['index'], array_keys($indexesToUnset)) === false)
          $content .= $goodBlock['content'];

        $goodBlock['content'] = '';
      } else
        $content .= $block['content'];
    }

    return $content;
  }
}

// We handle the edge case of the blocks.php file that is included via a template and needs MasterController,
// allowing the block.php file of the template engine system to work in production mode,
// by creating a class alias. Disabled when passing via the command line tasks.
if ($_SERVER['APP_ENV'] === 'prod' && PHP_SAPI !== 'cli')
  class_alias('\cache\php\MasterController', '\lib\myLibs\MasterController');
?>
