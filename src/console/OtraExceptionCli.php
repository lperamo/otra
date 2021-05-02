<?php
declare(strict_types=1);
namespace otra\console;

use Exception;
use JetBrains\PhpStorm\Pure;
use otra\OtraException;

/**
 * Shows an exception 'colorful' display for command line commands.
 *
 * @author Lionel Péramo
 */
class OtraExceptionCli extends Exception
{
  private const TYPE_WIDTH = 21, // the longest type is E_RECOVERABLE_ERROR so 16 and we add 5 to this
    FUNCTION_WIDTH = 49,
    LINE_WIDTH = 9,
    FILE_WIDTH = 85,
    ARGUMENTS_WIDTH = 51,
    KEY_VARIABLES = 'variables';

  /**
   * @param OtraException $exception
   */
  public function __construct(OtraException $exception)
  {
    parent::__construct();

    if (false === empty($exception->context))
      unset($exception->context[self::KEY_VARIABLES]);

    $exception->backtraces = $exception->getTrace();

    // Is the error code a native error code ?
    $exception->scode = OtraException::$codes[$exception->code] ?? 'UNKNOWN';
    $exception->message = preg_replace('/<br\s*\/?>/i', '', $exception->message);

    self::showMessage($exception);
  }

  /**
   * Converts the absolute path into 'BASE_PATH/CORE_PATH/CONSOLE_PATH + path' path like
   *
   * @param string $pathType 'BASE', 'CORE' or 'CONSOLE'
   * @param string $file
   *
   * @return string
   */
  private static function returnShortenFilePath(string $pathType, string $file) : string
  {
    return CLI_INFO_HIGHLIGHT . $pathType . '_PATH' . END_COLOR . ' + ' .
      mb_substr($file, mb_strlen(constant($pathType . '_PATH')));
  }

  /**
   * @param array  $backtraces
   * @param string $errorCode
   *
   * @return string
   */
  private static function getBacktracesOutput(array $backtraces, string $errorCode = '') : string
  {
    $backtracesOutput = '';

    /******************************
     * Write HEADERS of the table *
     ******************************/
    $backtracesOutput .=
      CLI_TABLE . '┌' . str_repeat('─',self::TYPE_WIDTH)
      . '┬' . str_repeat('─',self::FUNCTION_WIDTH)
      . '┬' . str_repeat('─',self::LINE_WIDTH)
      . '┬' . str_repeat('─',self::FILE_WIDTH)
      . '┬' . str_repeat('─',self::ARGUMENTS_WIDTH) . END_COLOR . PHP_EOL .
      self::consoleHeaders() .
      PHP_EOL .
      CLI_TABLE . '├' . str_repeat('─',self::TYPE_WIDTH) .
        '┼' . str_repeat('─',self::FUNCTION_WIDTH) .
        '┼' . str_repeat('─',self::LINE_WIDTH) .
        '┼' . str_repeat('─',self::FILE_WIDTH) .
        '┼' . str_repeat('─',self::ARGUMENTS_WIDTH) . END_COLOR . END_COLOR . PHP_EOL;

    /*******************************
     * Write the BODY of the table *
     *******************************/

    for($actualTraceIndex = 0, $trace = $backtraces, $maxTraceIndex = count($trace);
        $actualTraceIndex < $maxTraceIndex;
        $actualTraceIndex++)
    {
      $actualTrace = $trace[$actualTraceIndex];
      $actualTraceFile = $actualTrace['file'] ?? '';

      if (0 === $actualTraceIndex) unset($actualTrace['args'][self::KEY_VARIABLES]);

      $compositeColoredPath = true;

      if ($actualTraceFile !== '')
      {
        $actualTraceFile = str_replace('\\', '/', $actualTraceFile);

        if (str_contains($actualTraceFile, CONSOLE_PATH))
          $actualTraceFile = self::returnShortenFilePath('CONSOLE', $actualTraceFile);
        elseif (str_contains($actualTraceFile, CORE_PATH))
          $actualTraceFile = self::returnShortenFilePath('CORE', $actualTraceFile);
        elseif (str_contains($actualTraceFile, BASE_PATH))
          $actualTraceFile = self::returnShortenFilePath('BASE', $actualTraceFile);
        else
          $compositeColoredPath = false;
      } else
        $compositeColoredPath = false;

      $backtracesOutput .= CLI_TABLE . '| ' . END_COLOR .
        str_pad(0 === $actualTraceIndex ? $errorCode : '', self::TYPE_WIDTH - 1) .
        self::consoleLine($actualTrace, 'function', self::FUNCTION_WIDTH) .
        self::consoleLine($actualTrace, 'line', self::LINE_WIDTH) .
          /** FILE - Path is shortened to the essential in order to leave more place for the path's end */
        self::consoleLine(
          $actualTrace,
          'file',
          // If the path is composite e.g. : 'KIND_OF_PATH + File'; then no coloring is needed
          $compositeColoredPath ? self::FILE_WIDTH + 23 : self::FILE_WIDTH,
          $actualTraceFile
        ) .
          /** ARGUMENTS */
        CLI_TABLE . '|' . END_COLOR .
        ' NOT IMPLEMENTED YET' .
        PHP_EOL;

      // echo $now['args']; after args has been converted
    }

    // End of the table
    return $backtracesOutput . CLI_TABLE . '└' . str_repeat('─', self::TYPE_WIDTH)
      . '┴' . str_repeat('─', self::FUNCTION_WIDTH)
      . '┴' . str_repeat('─', self::LINE_WIDTH)
      . '┴' . str_repeat('─', self::FILE_WIDTH)
      . '┴' . str_repeat('─', self::ARGUMENTS_WIDTH) . END_COLOR . PHP_EOL;
  }

  /**
   * Shows an exception 'colorful' display for command line commands.
   *
   * @param OtraException $exception
   */
  public static function showMessage(OtraException $exception) : void
  {
    echo CLI_ERROR, PHP_EOL, 'PHP exception', PHP_EOL, '=============', END_COLOR, PHP_EOL, PHP_EOL;

    if (isset($exception->scode))
    {
      $exceptionFile = $exception->file;

      if (str_contains($exceptionFile, CONSOLE_PATH))
        $exceptionFile = str_replace(CONSOLE_PATH, 'CONSOLE_PATH + ', $exceptionFile);
      elseif (str_contains($exceptionFile, CORE_PATH))
        $exceptionFile = str_replace(CONSOLE_PATH, 'CORE_PATH + ', $exceptionFile);
      elseif (str_contains($exceptionFile, BASE_PATH))
        $exceptionFile = str_replace(CONSOLE_PATH, 'BASE_PATH + ', $exceptionFile);

      echo 'Error type ', CLI_INFO_HIGHLIGHT, $exception->scode, END_COLOR, ' in ', CLI_INFO_HIGHLIGHT, $exceptionFile, END_COLOR,
        ' at line ', CLI_INFO_HIGHLIGHT, $exception->line, END_COLOR, PHP_EOL, $exception->message, PHP_EOL;
    }

    echo self::getBacktracesOutput($exception->backtraces, $exception->scode);

    if ($exception->context !== [])
    {
      echo 'And the context...', PHP_EOL;
      echo self::getBacktracesOutput($exception->context);
    }
  }

  /**
   * Returns the text that shows the headers for a unicode table (command line style)
   *
   *
   * @return string
   */
  private static function consoleHeaders() : string
  {
    $output = '';

    foreach(['Type', 'Function', 'Line', 'File', 'Arguments'] as $value)
    {
      $output .= CLI_TABLE . '│' . CLI_TABLE_HEADER .
        str_pad(' ' . $value, constant('self::' . mb_strtoupper($value) . '_WIDTH'));
    }

    return $output;
  }

  /**
   * Returns the content of a stack trace row in console style.
   *
   * @param array  $rowData          Data of a stack trace line
   * @param string $columnName
   * @param int    $width
   * @param string $alternateContent
   *
   * @return string
   */
  #[Pure] private static function consoleLine(
    array $rowData,
    string $columnName,
    int $width,
    string $alternateContent = ''
  ) : string
  {
    return CLI_TABLE . '│' . END_COLOR .
      str_pad(isset($rowData[$columnName])
        ? ' ' . ('' === $alternateContent ? $rowData[$columnName] : $alternateContent) . ' '
        : ' -',
        $width
      );
  }
}

