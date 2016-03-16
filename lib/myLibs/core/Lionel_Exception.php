<?
/**
 * Customized exception class
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace lib\myLibs\core;

use lib\myLibs\core\Controller,
    config\Routes;

require_once BASE_PATH . 'config/All_Config.php';
require BASE_PATH . 'lib\myLibs\core\Debug_Tools.php';

class Lionel_Exception extends \Exception
{
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

    die('cli' == php_sapi_name() ? $this->consoleMessage() : $this->errorMessage());
  }

  public function errorMessage() : string
  {
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
      'code' => $this->code,
      'file' => $this->file,
      'line' => $this->line,
      'context' => $this->context,
      'backtraces' => $this->getTrace()
      ]
    );
  }

  public function consoleMessage()
  {
    if(! empty($this->context))
    {
      unset($this->context['variables']);
//      convertArrayToShowableConsole($this->context, 'Variables');
    }

    $backtraces = $this->getTrace();

    require(BASE_PATH . 'lib\myLibs\core\views\exceptionConsole.phtml');
  }
}
?>
