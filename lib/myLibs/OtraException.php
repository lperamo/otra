<?php
/**
 * Customized exception class
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace lib\myLibs;

use lib\myLibs\{Controller, console\OtraExceptionCLI};
use config\Routes;

// Sometimes it is already defined ! so we put '_once' ...
require_once CORE_PATH . 'debugTools.php';

class OtraException extends \Exception
{
  public static array $codes = [
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

  public array $backtraces;
  public array $context;
  // String version of error code
  public string $scode;

  /**
   * OtraException constructor.
   *
   * @param string $message
   * @param int    $code
   * @param string $file
   * @param int    $line
   * @param array  $context
   *
   * @throws OtraException
   */
  public function __construct(string $message = 'Error !', int $code = NULL, string $file = '', int $line = NULL, $context = [])
  {
    $this->message = str_replace('<br>', PHP_EOL, $message);
    $this->code = (null !== $code) ? $code : $this->getCode();
    $this->file = str_replace('\\', '/', (('' == $file) ? $this->getFile() : $file));
    $this->line = ('' === $line) ? $this->getLine() : $line;
    $this->context = $context;

    if ('cli' === PHP_SAPI)
      new OtraExceptionCLI($this);
    else
      echo $this->errorMessage(); // @codeCoverageIgnore
  }

  /**
   * Shows a custom exception page.
   *
   * @throws OtraException
   */
  private function errorMessage() : string
  {
    require_once BASE_PATH . 'config/AllConfig.php';
    $route = 'exception';

    // Cleans all the things processed before in order to not perturb the exception page
    ob_clean();

    $renderController = new Controller(
      [
        'bundle' => Routes::$_[$route]['chunks'][1] ?? '',
        'module' =>  Routes::$_[$route]['chunks'][2] ?? '',
        'route' => $route,
        'hasCssToLoad' => '',
        'hasJsToLoad' => ''
      ]
    );
    $renderController->viewPath = CORE_VIEWS_PATH;
    $renderController::$path = $_SERVER['DOCUMENT_ROOT'] . '..';

    if (false === empty($this->context))
    {
      unset($this->context['variables']);
      $showableContext = createShowableFromArray($this->context, 'Variables');
    } else
      $showableContext = '';

    // Is the error code a native error code ?
    $code = true === isset(self::$codes[$this->code]) ? self::$codes[$this->code] : 'UNKNOWN';
    http_response_code(MasterController::HTTP_INTERNAL_SERVER_ERROR);

    return $renderController->renderView(
      '/exception.phtml',
      [
        'message' => $this->message,
        'code' => $code,
        'file' => mb_substr($this->file, mb_strlen(BASE_PATH)),
        'line' => $this->line,
        'context' => $showableContext,
        'backtraces' => $this->getTrace()
      ]
    );
  }
}
?>
