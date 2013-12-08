<?php
/** Customized exception class
 *
 * @author Lionel Péramo */
namespace lib\myLibs\core;

use lib\myLibs\core\Controller,
    lib\myLibs\core\Debug_Tools;

require_once __DIR__ . '/../../../config/All_Config.php';

class Lionel_Exception extends \Exception
{
  public function __construct($message = 'Error !', $code = '', $file = '', $line = '', $context = '')
  {
    $this->message = $message;
    $this->code = ('' != $code) ? $code : $this->getCode();
    $this->file = ('' == $file) ? $this->getFile() : $file;
    $this->line = ('' == $line) ? $this->getLine() : $line;
    $this->context = $context;
  }

  public function errorMessage()
  {
    ob_clean();
    $renderController = new Controller();
    // $renderController->route = '';
    $renderController->viewPath = CORE_VIEWS_PATH;
    $renderController::$path = $_SERVER['DOCUMENT_ROOT'] . '..';

    if(! empty($this->context))
    {
      unset($this->context['variables']);
      convertArrayToShowable($this->context, 'Variables');
    }

    return $renderController->renderView('/exception.phtml', array(
      'message' =>$this->message,
      'code' => $this->code,
      'file' => $this->file,
      'line' => $this->line,
      'context' => $this->context,
      'backtraces' => $this->getTrace()
      )
    );
  }
}
