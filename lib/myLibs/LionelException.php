<?
/**
 * Customized exception class
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace lib\myLibs;

use lib\myLibs\{Controller, console\LionelExceptionCLI};
use config\Routes;

// Sometimes it is already defined ! so we put '_once' ...
require_once BASE_PATH . 'lib\myLibs\DebugTools.php';

class LionelException extends \Exception
{
  public static $codes = [
    E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
    E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
    E_CORE_ERROR        => 'E_CORE_ERROR',
    E_CORE_WARNING      => 'E_CORE_WARNING',
    E_DEPRECATED        => 'E_DEPRECATED',
    E_ERROR             => 'E_ERROR',
    E_NOTICE            => 'E_NOTICE',
    E_PARSE             => 'E_PARSE',
    E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
    E_STRICT            => 'E_STRICT',
    E_USER_DEPRECATED   => 'E_USER_DEPRECATED',
    E_USER_ERROR        => 'E_USER_ERROR',
    E_USER_NOTICE       => 'E_USER_NOTICE',
    E_USER_WARNING      => 'E_USER_WARNING',
    E_WARNING           => 'E_WARNING'
  ];

  /**
   * LionelException constructor.
   *
   * @param string $message
   * @param int    $code
   * @param string $file
   * @param int    $line
   * @param string $context
   */
  public function __construct(string $message = 'Error !', int $code = NULL, string $file = '', int $line = NULL, $context = '')
  {
    $this->message = str_replace('<br>', PHP_EOL, $message);
    $this->code = ('' != $code) ? $code : $this->getCode();
    $this->file = str_replace('\\', '/', (('' == $file) ? $this->getFile() : $file));
    $this->line = ('' == $line) ? $this->getLine() : $line;
    $this->context = $context;

    echo 'cli' == php_sapi_name() ? $this->consoleMessage() : $this->errorMessage();
    return;
  }

  /**
   * Shows a custom exception page.
   */
  public function errorMessage() : string
  {
    require_once BASE_PATH . 'config/AllConfig.php';
    $route = 'exception';

    ob_clean();
    $renderController = new Controller();
    $renderController->route = $route;
    $renderController->bundle = Routes::$_[$route]['chunks'][1] ?? '';
    $renderController->module = Routes::$_[$route]['chunks'][2] ?? '';
    $renderController->viewPath = CORE_VIEWS_PATH;
    $renderController::$path = $_SERVER['DOCUMENT_ROOT'] . '..';

    if (false === empty($this->context))
    {
      unset($this->context['variables']);
      convertArrayToShowable($this->context, 'Variables');
    } else
      $this->context = '';

    // Is the error code a native error code ?
    $code = true === isset(self::$codes[$this->code]) ? self::$codes[$this->code] : 'UNKNOWN';
    http_response_code(MasterController::HTTP_INTERNAL_SERVER_ERROR);

    return $renderController->renderView('/exception.phtml', [
        'message' => $this->message,
        'code' => $code,
        'file' => substr($this->file, strlen(BASE_PATH)),
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
    if (false === empty($this->context))
    {
      unset($this->context['variables']);
//      convertArrayToShowableConsole($this->context, 'Variables');
    }

    $this->backtraces = $this->getTrace();

    // Is the error code a native error code ?
    $this->code = true === isset(self::$codes[$this->code]) ? self::$codes[$this->code] : 'UNKNOWN';
    $this->message = preg_replace('/\<br\s*\/?\>/i', '', $this->message);

    // If there is no ClassMap.php, we cannot use the 'use' statement
    // so we are forced to use a 'require_once' statement
    if (true === class_exists('lib\myLibs\console\LionelExceptionCLI'))
      LionelExceptionCLI::showMessage($this);
    else
    {
      require_once BASE_PATH . 'lib/myLibs/console/LionelExceptionCLI.php';
      LionelExceptionCLI::showMessage($this);
    }
//    require(BASE_PATH . 'lib\myLibs\views\exceptionConsole.phtml');
  }
}
?>