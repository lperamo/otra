<?php
declare(strict_types=1);
namespace otra\console;

use Exception;
use JetBrains\PhpStorm\Pure;
use otra\OtraException;
use const otra\cache\php\{BASE_PATH, CONSOLE_PATH, CORE_PATH, DIR_SEPARATOR};

/**
 * Shows an exception 'colorful' display for command line commands.
 *
 * @author Lionel Péramo
 */
class OtraExceptionCli extends Exception
{
  private const
    KEY_VARIABLES = 'variables',
    TABLE_WIDTHS = ['function', 'line', 'file', 'arguments'],
    TOP_START_SEPARATOR = '┌',
    TOP_INTERSECTION_SEPARATOR = '┬',
    TOP_END_SEPARATOR = '┐',
    MEDIUM_START_SEPARATOR = '├',
    VERTICAL_SEPARATOR = '│',
    INTERSECTION_OPERATOR = '┼',
    BOTTOM_INTERSECTION_SEPARATOR = '┴',
    BOTTOM_END_SEPARATOR = '┘',
    HORIZONTAL_SEPARATOR = '─',
    CELL_PADDING = 2,
    UNKNOWN_LENGTH = 7,
    LINE_HEADER_LENGTH = 4,
    NOT_IMPLEMENTED_YET_LENGTH = 19,
    GET_LONGER_STRING_CALLBACK = 'self::getLongerString';

  private static int
    $typeLongestString,
    $functionLongestString,
    $lineLongestString,
    $fileLongestString,
    $argumentsLongestString;

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
      mb_substr($file, mb_strlen(constant('otra\\cache\\php\\' . $pathType . '_PATH')));
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

      echo 'Error type ', CLI_INFO_HIGHLIGHT, $exception->scode, END_COLOR, ' in ', CLI_INFO_HIGHLIGHT, $exceptionFile,
      END_COLOR, ' at line ', CLI_INFO_HIGHLIGHT, $exception->line, END_COLOR, PHP_EOL, $exception->message, PHP_EOL;
    }

    $exceptionBacktraces = $exception->backtraces;

    // If there is a context, we add it
    if (!empty($exception->context))
      array_push($exceptionBacktraces, ...$exception->context);

    // Preparing table decorations
    $tableEnd = $headersTop = $headersEnd = '';

    $filenames = array_column($exceptionBacktraces, 'file');
    $formattedFilenames = [];
    $compositeColoredPath = true;

    foreach ($filenames as &$filename)
    {
      $compositeColoredPath = true;

      if ($filename !== '')
      {
        $filename = str_replace('\\', DIR_SEPARATOR, $filename);

        if (str_contains($filename, CONSOLE_PATH))
          $filename = self::returnShortenFilePath('CONSOLE', $filename);
        elseif (str_contains($filename, CORE_PATH))
          $filename = self::returnShortenFilePath('CORE', $filename);
        elseif (str_contains($filename, BASE_PATH))
          $filename = self::returnShortenFilePath('BASE', $filename);
        else
          $compositeColoredPath = false;
      }
      else
        $compositeColoredPath = false;

      $formattedFilenames[] = [
        'compositeColoredPath' => $compositeColoredPath,
        'file' => $filename
      ];
    }

    $exceptionCodeLen = mb_strlen($exception->scode);
    self::$typeLongestString = ($exceptionCodeLen > self::UNKNOWN_LENGTH
        ? $exceptionCodeLen
        : self::UNKNOWN_LENGTH) + self::CELL_PADDING;

    $functionLen = mb_strlen(array_reduce(
      array_column($exceptionBacktraces, 'function'),
      self::GET_LONGER_STRING_CALLBACK,
      ''
    ));
    self::$functionLongestString = ($functionLen > 8 ? $functionLen : 8) + self::CELL_PADDING;
    $lineCodeLen = mb_strlen(array_reduce(
      array_column($exceptionBacktraces, 'line'),
      self::GET_LONGER_STRING_CALLBACK,
      ''
    ));

    self::$lineLongestString = ($lineCodeLen > self::LINE_HEADER_LENGTH
        ? $lineCodeLen
        : self::LINE_HEADER_LENGTH) + self::CELL_PADDING;
    self::$fileLongestString = mb_strlen(array_reduce(
      $filenames,
      self::GET_LONGER_STRING_CALLBACK,
      ''
    ));

    self::$argumentsLongestString = (self::$typeLongestString > self::NOT_IMPLEMENTED_YET_LENGTH
        ? self::$typeLongestString
        : self::NOT_IMPLEMENTED_YET_LENGTH) + self::CELL_PADDING;

    foreach(self::TABLE_WIDTHS as $headerWidth)
    {
      $separatorsLength = self::${$headerWidth . 'LongestString'};

      // adjustments due to color changes (curious that is not dependent of $compositeColoredPath
      if ($headerWidth === 'file')
        $separatorsLength -= 21;

      $separators = str_repeat(self::HORIZONTAL_SEPARATOR, $separatorsLength);
      $headersTop .= self::TOP_INTERSECTION_SEPARATOR . $separators;
      $headersEnd .= self::INTERSECTION_OPERATOR . $separators;
      $tableEnd .= self::BOTTOM_INTERSECTION_SEPARATOR . $separators;
    }

    unset($compositeColoredPath);

    $headersTop .= self::TOP_END_SEPARATOR;
    $headersEnd .= self::VERTICAL_SEPARATOR;
    $tableEnd .= self::BOTTOM_END_SEPARATOR;

    unset($headerWidth);

    /******************************
     * Write HEADERS of the table *
     ******************************/
    $backtracesOutput = CLI_TABLE . self::TOP_START_SEPARATOR .
      str_repeat(self::HORIZONTAL_SEPARATOR, self::$typeLongestString) .
      $headersTop . END_COLOR . PHP_EOL .
      self::consoleHeaders() . PHP_EOL .
      CLI_TABLE . self::MEDIUM_START_SEPARATOR .
      str_repeat(self::HORIZONTAL_SEPARATOR, self::$typeLongestString) . $headersEnd .
      END_COLOR . END_COLOR . PHP_EOL;

    /*******************************
     * Write the BODY of the table *
     *******************************/

    unset($exceptionBacktraces[0]['args'][self::KEY_VARIABLES]);

    for ($actualTraceIndex = 0, $maxTraceIndex = count($exceptionBacktraces);
         $actualTraceIndex < $maxTraceIndex;
         $actualTraceIndex++)
    {
      $actualTrace = $exceptionBacktraces[$actualTraceIndex];

      $backtracesOutput .= CLI_TABLE . self::VERTICAL_SEPARATOR . ' ' . END_COLOR .
        str_pad(
          0 === $actualTraceIndex ? $exception->scode : '',
          self::$typeLongestString - 1
        ) .
        self::consoleLine($actualTrace, 'function', self::$functionLongestString) .
        self::consoleLine($actualTrace, 'line', self::$lineLongestString) .
        /** FILE - Path is shortened to the essential in order to leave more place for the path's end */
        (isset($formattedFilenames[$actualTraceIndex])
        ? self::consoleLine(
          $actualTrace,
          'file',
          // If the path is composite e.g. : 'KIND_OF_PATH + File'; then no coloring is needed
          self::$fileLongestString +
          ($formattedFilenames[$actualTraceIndex]['compositeColoredPath'] && isset($actualTrace['file']) ? 2 : -21),
          $formattedFilenames[$actualTraceIndex]['file']
        )
        : self::consoleLine(
          $actualTrace,
          'file',
          // If the path is composite e.g. : 'KIND_OF_PATH + File'; then no coloring is needed
          self::$fileLongestString - 21
        )) .
        /** ARGUMENTS */
        CLI_TABLE . self::VERTICAL_SEPARATOR . CLI_BASE .
        ' NOT IMPLEMENTED YET ' . CLI_TABLE . self::VERTICAL_SEPARATOR . END_COLOR .
        PHP_EOL;

      // echo $now['args']; after args has been converted
    }

    $backtracesOutput .= CLI_TABLE . '└' .
      str_repeat(self::HORIZONTAL_SEPARATOR, self::$typeLongestString) . $tableEnd . END_COLOR . PHP_EOL;

    // End of the table
    echo $backtracesOutput;
  }

  /**
   * Returns the text that shows the headers for a unicode table (command line style)
   *
   * @return string
   */
  private static function consoleHeaders() : string
  {
    $output = '';

    foreach(['Type', 'Function', 'Line', 'File', 'Arguments'] as $value)
    {
      $output .= CLI_TABLE . self::VERTICAL_SEPARATOR . CLI_TABLE_HEADER .
        str_pad(' ' . $value, ($value === 'File' ? -21 : 0) + self::${lcfirst($value) . 'LongestString'});
    }

    return $output . CLI_TABLE . self::VERTICAL_SEPARATOR;
  }

  /**
   * Returns the content of a stack trace row in console style.
   *
   * @param array  $rowData          Data of a stack trace line
   * @param string $columnName
   * @param int    $padLength
   * @param string $alternateContent
   *
   * @return string
   */
  #[Pure] private static function consoleLine(
    array $rowData,
    string $columnName,
    int $padLength,
    string $alternateContent = ''
  ) : string
  {
    return CLI_TABLE . self::VERTICAL_SEPARATOR . END_COLOR . str_pad((isset($rowData[$columnName])
        ? ' ' . ('' === $alternateContent ? $rowData[$columnName] : $alternateContent) . ' '
        : ' -'), $padLength);
  }

  /**
   * @param string $firstString
   * @param string $secondString
   *
   * @return string
   */
  private static function getLongerString(string $firstString, string $secondString) : string
  {
    return (mb_strlen($firstString) > mb_strlen($secondString)) ? $firstString : $secondString;
  }
}
