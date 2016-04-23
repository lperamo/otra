<?
/**
 * Customized exception class
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace lib\myLibs\core;

use lib\myLibs\core\Controller,
    config\Routes;


require BASE_PATH . 'lib\myLibs\core\Debug_Tools.php';

class Lionel_Exception extends \Exception
{
  private static $codes = [
    E_COMPILE_ERROR => 'E_COMPILE_ERROR',
    E_COMPILE_WARNING => 'E_COMPILE_WARNING',
    E_CORE_ERROR => 'E_CORE_ERROR',
    E_CORE_WARNING => 'E_CORE_WARNING',
    E_DEPRECATED => 'E_DEPRECATED',
    E_ERROR => 'E_ERROR',
    E_NOTICE => 'E_NOTICE',
    E_PARSE => 'E_PARSE',
    E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
    E_STRICT => 'E_STRICT',
    E_USER_DEPRECATED => 'E_USER_DEPRECATED',
    E_USER_ERROR => 'E_USER_ERROR',
    E_USER_NOTICE => 'E_USER_NOTICE',
    E_USER_WARNING => 'E_USER_WARNING',
    E_WARNING => 'E_WARNING'
  ];

  /**
   * Lionel_Exception constructor.
   *
   * @param string $message
   * @param int    $code
   * @param string $file
   * @param int    $line
   * @param string $context
   */
  public function __construct(string $message = 'Error !', int $code = NULL, string $file = '', int $line = NULL, $context = '')
  {
    $this->message = $message;
    $this->code = ('' != $code) ? $code : $this->getCode();
    $this->file = ('' == $file) ? $this->getFile() : $file;
    $this->line = ('' == $line) ? $this->getLine() : $line;
    $this->context = $context;

    echo 'cli' == php_sapi_name() ? $this->consoleMessage() : $this->errorMessage();
    exit(1);
  }

  /**
   * Shows a custom exception page.
   */
  public function errorMessage() : string
  {
    require_once BASE_PATH . 'config/All_Config.php';
    $route = 'exception';

    ob_clean();
    $renderController = new Controller();
    $renderController->route = $route;
    $renderController->bundle = Routes::$_[$route]['chunks'][1] ?? '';
    $renderController->module = Routes::$_[$route]['chunks'][2] ?? '';
    $renderController->viewPath = CORE_VIEWS_PATH;
    $renderController::$path = $_SERVER['DOCUMENT_ROOT'] . '..';

    if(! empty($this->context))
    {
      unset($this->context['variables']);
      convertArrayToShowable($this->context, 'Variables');
    }

    return $renderController->renderView('/exception.phtml', [
      'message' =>$this->message,
      'code' => self::$codes[$this->code],
      'file' => $this->file,
      'line' => $this->line,
      'context' => $this->context,
      'backtraces' => $this->getTrace()
      ]
    );
  }

  /**
   * Shows an exception 'colorful' display for command line commands.
   */
  public function consoleMessage()
  {
    if(! empty($this->context))
    {
      unset($this->context['variables']);
//      convertArrayToShowableConsole($this->context, 'Variables');
    }

    $backtraces = $this->getTrace();

    // Is the error code a native error code ?
    $this->code = isset(self::$codes[$this->code]) ? self::$codes[$this->code] : 'UNKNOWN';
    $this->message = preg_replace('/\<br\s*\/?\>/i', '', $this->message);

    require(BASE_PATH . 'lib\myLibs\core\views\exceptionConsole.phtml');
  }
}
?>
