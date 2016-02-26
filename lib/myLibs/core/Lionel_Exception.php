<?
/**
 * Customized exception class
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace lib\myLibs\core;

use lib\myLibs\core\Controller,
    lib\myLibs\core\Debug_Tools,
    config\Routes;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/All_Config.php';

class Lionel_Exception extends \Exception
{
  public function __construct(string $message = 'Error !', string $code = '', string $file = '', string $line = '', $context = '')
  {
    $this->message = $message;
    $this->code = ('' != $code) ? $code : $this->getCode();
    $this->file = ('' == $file) ? $this->getFile() : $file;
    $this->line = ('' == $line) ? $this->getLine() : $line;
    $this->context = $context;

    die($this->errorMessage());
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
}
?>
