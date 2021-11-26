<?php
/**
 * Customized exception class
 *
 * @author Lionel Péramo
 */
declare(strict_types=1);

namespace otra;

use otra\console\OtraExceptionCli;
use otra\config\Routes;
use Error;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use const otra\cache\php\{APP_ENV, BASE_PATH, CORE_VIEWS_PATH, DEV, DIR_SEPARATOR, PROD};
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR};

/**
 * @package otra
 */
class OtraException extends Exception
{
  /** @var array<int, string> */
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
   * @param ?int|mixed $code
   * @param string     $file
   * @param ?int       $line
   * @param array|null $context
   * @param bool       $exit    True only if we came from a console task that wants to do an exit.
   *
   * @throws OtraException
   */

  public function __construct(
    string $message = 'Error !',
    mixed $code = NULL,
    string $file = '',
    ?int $line = NULL,
    public array|null $context = [],
    private readonly bool $exit = false
  )
  {
    parent::__construct();
    $this->code = (null !== $code) ? $code : $this->getCode();

    // When $otraCliWarning is true then we only need the error code that will be used as exit code
    if ($exit)
      return;

    $this->message = str_replace('<br>', PHP_EOL, $message);
    $this->file = str_replace('\\', DIR_SEPARATOR, (('' == $file) ? $this->getFile() : $file));
    $this->line = (null === $line) ? $this->getLine() : $line;

    if ('cli' === PHP_SAPI)
      new OtraExceptionCli($this);
    elseif ($_SERVER[APP_ENV] !== PROD)
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
        echo CLI_ERROR . 'Error in ' . CLI_INFO_HIGHLIGHT . $this->file . CLI_ERROR . ':' . CLI_INFO_HIGHLIGHT .
          $this->line . CLI_ERROR . ' : ' . $this->message . END_COLOR . PHP_EOL;
        throw new OtraException(code: 1, exit: true);
      } else
        return '<span style="color: #e00;">Error in </span><span style="color: #0aa;">' . $this->file .
          '</span>:<span style="color: #0aa;">' . $this->line . '</span><span style="color: #e00;"> : ' . $this->message .
          '</span>';
    }

    $renderController->viewPath = CORE_VIEWS_PATH . 'errors/';
    $renderController::$path = $_SERVER['DOCUMENT_ROOT'] . '..';

    // Is the error code a native error code ?
    $errorCode = self::$codes[$this->code] ?? 'UNKNOWN';
    http_response_code(MasterController::HTTP_CODES['HTTP_INTERNAL_SERVER_ERROR']);

    $traces = $this->getTrace();
    $simplifiedFilePath = mb_substr($this->file, mb_strlen(BASE_PATH));

    // This test avoids trying to render an exception that cannot be rendered by classic ways...
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

      throw new OtraException(code: 1, exit: true);
    }

    return $renderController->renderView(
      '/errors/exception.phtml',
      [
        'backtraces' => $traces,
        'context' => (array)$this->context,
        'errorCode' => $errorCode,
        'fileLine' => $this->line,
        'fileName' => $simplifiedFilePath,
        'message' => $this->message
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
   * @param Exception|Error|OtraException $exception Can be TypeError, OtraException, maybe something else.
   *
   * @throws OtraException
   */
  public static function exceptionHandler(Exception|Error|OtraException $exception) : never
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
        $exception->exit ?? false
      );
    }

    exit($exception->getCode());
  }
}
