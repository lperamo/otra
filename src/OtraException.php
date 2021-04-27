<?php
/**
 * Customized exception class
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace otra;

use otra\{Controller, console\OtraExceptionCli};
use config\Routes;
use Exception;
use JetBrains\PhpStorm\NoReturn;

/**
 * @package otra
 */
class OtraException extends Exception
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

  // String version of error code
  public string $scode;
  public array $backtraces;

  /**
   * OtraException constructor.
   *
   * @param string     $message
   * @param int|null   $code
   * @param string     $file
   * @param int|null   $line
   * @param array|null $context
   * @param bool       $otraCliWarning True only if we came from a console task that wants to do an exit.
   *
   * @throws OtraException
   */

  public function __construct(
    string $message = 'Error !',
    int|string|null $code = NULL,
    string $file = '',
    ?int $line = NULL,
    public array|null $context = [],
    private bool $isOtraCliWarning = false)
  {
    parent::__construct();
    $this->code = (null !== $code) ? $code : $this->getCode();

    // When $otraCliWarning is true then we only need the error code that will be used as exit code
    if ($isOtraCliWarning)
      return;

    $this->message = str_replace('<br>', PHP_EOL, $message);
    $this->file = str_replace('\\', '/', (('' == $file) ? $this->getFile() : $file));
    $this->line = (null === $line) ? $this->getLine() : $line;

    if ('cli' === PHP_SAPI)
      new OtraExceptionCli($this);
    elseif ($_SERVER['APP_ENV'] !== PROD)
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
    $route = 'otra_exception';

    // Cleans all the things processed before in order to not perturb the exception page
    ob_clean();

    // If the error is that we do not found the Controller class then we directly show the message.
    try {
      $renderController = new Controller(
        [
          'bundle' => Routes::$allRoutes[$route]['chunks'][1] ?? '',
          'module' =>  Routes::$allRoutes[$route]['chunks'][2] ?? '',
          'route' => $route,
          'hasCssToLoad' => false,
          'hasJsToLoad' => false
        ]
      );
    } catch(Exception $exception)
    {
      if (PHP_SAPI === 'cli')
      {
        echo CLI_ERROR . 'Error in ' . CLI_INFO_HIGHLIGHT . $this->file . CLI_ERROR . ':' . CLI_INFO_HIGHLIGHT . $this->line .
          CLI_ERROR . ' : ' . $this->message . END_COLOR . PHP_EOL;
        throw new OtraException('', 1, '', NULL, [], true);
      } else
        return '<span style="color: #e00;">Error in </span><span style="color: #0aa;">' . $this->file .
          '</span>:<span style="color: #0aa;">' . $this->line . '</span><span style="color: #e00;"> : ' . $this->message .
          '</span>';
    }

    $renderController->viewPath = CORE_VIEWS_PATH . 'errors/';
    $renderController::$path = $_SERVER['DOCUMENT_ROOT'] . '..';

    // Is the error code a native error code ?
    $errorCode = isset(self::$codes[$this->code]) ? self::$codes[$this->code] : 'UNKNOWN';
    http_response_code(MasterController::HTTP_CODES['HTTP_INTERNAL_SERVER_ERROR']);

    $traces = $this->getTrace();
    $simplifiedFilePath = mb_substr($this->file, mb_strlen(BASE_PATH));

    // This test avoid trying to render an exception that cannot be rendered by classic ways...
    if (isset($traces[1], $traces[1]['function']) && $traces[1]['function'] === 'renderView')
    {
      if ($_SERVER[APP_ENV] === DEV)
      {
        [$message, $errorCode, $fileName, $fileLine, $context, $backtraces] = [
          $this->message,
          $errorCode,
          $simplifiedFilePath,
          $this->line,
          (array)$this->context,
          $traces
        ];
        require $renderController->viewPath . 'renderedWithoutController.phtml';
      } else
        echo 'A major problem has occurred. Sorry for the inconvenience. Please contact the site administrator.';

      throw new \otra\OtraException('', 1, '', NULL, [], true);
    }

    return $renderController->renderView(
      '/errors/exception.phtml',
      [
        'message' => $this->message,
        'errorCode' => $errorCode,
        'fileName' => $simplifiedFilePath,
        'fileLine' => $this->line,
        'context' => (array)$this->context,
        'backtraces' => $traces
      ],
      false,
      false
    );
  }

  /**
   * To use with set_error_handler().
   *
   * @param int        $errno
   * @param string     $message
   * @param string     $fileName
   * @param int        $fileLine
   * @param array|null $context
   *
   * @throws OtraException
   */
  #[NoReturn] public static function errorHandler(
    int $errno,
    string $message,
    string $fileName,
    int $fileLine,
    ?array $context = null
  ) : void
  {
    if (PHP_SAPI === 'cli')
      exit($errno);

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 'XMLHttpRequest' === $_SERVER['HTTP_X_REQUESTED_WITH'])
      // json sent if it was an AJAX request
      echo '{"success": "exception", "msg":' . json_encode(new OtraException($message)) . '}';
    else
      new OtraException($message, $errno, $fileName, $fileLine, $context);

    exit($errno);
  }

  /**
   * To use with set_exception_handler().
   *
   * @param Exception|\Error|OtraException $exception Can be TypeError, OtraException, maybe something else.
   *
   * @throws OtraException
   */
  #[NoReturn] public static function exceptionHandler(Exception|\Error|OtraException $exception) : void
  {
    if (PHP_SAPI === 'cli' && $exception instanceof OtraException)
      exit($exception->getCode());

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 'XMLHttpRequest' === $_SERVER['HTTP_X_REQUESTED_WITH'])
      // json sent if it was an AJAX request
      echo '{"success": "exception", "msg":' . json_encode(new OtraException($exception->getMessage())) . '}';
    else
      {
        new OtraException(
          $exception->getMessage(),
          $exception->getCode(),
          $exception->getFile(),
          $exception->getLine(),
          $exception->getTrace(),
          $exception->isOtraCliWarning
        );}

    exit($exception->getCode());
  }
}
