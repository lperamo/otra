<?php
/**
 * Customized console exception class
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace otra\console;

use otra\OtraException;

/**
 * Shows an exception 'colorful' display for command line commands.
 */
class OtraExceptionCli extends \Exception
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
    {
      unset($exception->context[self::KEY_VARIABLES]);
//      createShowableFromArrayConsole($this->context, 'Variables');
    }

    $exception->backtraces = $exception->getTrace();

    // Is the error code a native error code ?
    $exception->scode = true === isset(OtraException::$codes[$exception->code]) ? OtraException::$codes[$exception->code] : 'UNKNOWN';
    $exception->message = preg_replace('/\<br\s*\/?\>/i', '', $exception->message);

    self::showMessage($exception);
//    require(CORE_PATH . 'views\exceptionConsole.phtml');
  }

  /**
   * Converts the absolute path into 'BASE_PATH/CORE_PATH/CONSOLE_PATH + path' path like
   *
   * @param string $pathType 'BASE', 'CORE' or 'CONSOLE'
   * @param string $file
   *
   * @return string
   */
  private static function returnShortenFilePath(string $pathType, string &$file) : string
  {
    return CLI_BLUE . $pathType . '_PATH' . CLI_LIGHT_BLUE . ' + ' .
      mb_substr($file, mb_strlen(constant($pathType . '_PATH')));
  }

  /**
   * Shows an exception 'colorful' display for command line commands.
   *
   * @param OtraException $exception
   */
  public static function showMessage(OtraException $exception)
  {
    echo CLI_RED, PHP_EOL, 'PHP exception', PHP_EOL, '=============', END_COLOR, PHP_EOL, PHP_EOL;

    if (true === isset($exception->scode))
    {
      $exceptionFile = $exception->file;

      if (strpos($exceptionFile, CONSOLE_PATH) !== false)
        $exceptionFile = str_replace(CONSOLE_PATH, 'CONSOLE_PATH + ', $exceptionFile);
      elseif (strpos($exceptionFile, CORE_PATH) !== false)
        $exceptionFile = str_replace(CONSOLE_PATH, 'CORE_PATH + ', $exceptionFile);
      elseif (strpos($exceptionFile, BASE_PATH) !== false)
        $exceptionFile = str_replace(CONSOLE_PATH, 'BASE_PATH + ', $exceptionFile);

        echo 'Error type ', CLI_CYAN, $exception->scode, END_COLOR, ' in ', CLI_CYAN, $exceptionFile, END_COLOR,
        ' at line ', CLI_CYAN, $exception->line, END_COLOR, PHP_EOL, $exception->message, PHP_EOL;
    }

    /******************************
     * Write HEADERS of the table *
     ******************************/
    echo PHP_EOL,
      CLI_LIGHT_BLUE, '┌' . str_repeat('─', self::TYPE_WIDTH)
      . '┬' . str_repeat('─', self::FUNCTION_WIDTH)
      . '┬' . str_repeat('─', self::LINE_WIDTH)
      . '┬' . str_repeat('─', self::FILE_WIDTH)
      . '┬' . str_repeat('─', self::ARGUMENTS_WIDTH), END_COLOR, PHP_EOL,
      self::consoleHeaders(['Type', 'Function', 'Line', 'File', 'Arguments']),
      PHP_EOL,
      CLI_LIGHT_BLUE, '├' . str_repeat('─', self::TYPE_WIDTH) .
      '┼' . str_repeat('─', self::FUNCTION_WIDTH) .
      '┼' . str_repeat('─', self::LINE_WIDTH) .
      '┼' . str_repeat('─',self::FILE_WIDTH) .
      '┼' . str_repeat('─',self::ARGUMENTS_WIDTH), END_COLOR, END_COLOR, PHP_EOL;

    /*******************************
     * Write the BODY of the table *
     *******************************/
    for($i = 0, $trace = $exception->backtraces, $max = count($trace); $i < $max; $i += 1)
    {
      $now = $trace[$i];

      if(0 === $i) unset($now['args'][self::KEY_VARIABLES]);

      createShowableFromArrayConsole($now['args'], 'Arguments', self::KEY_VARIABLES);

      $compositeColoredPath = true;

      if (isset($now['file']))
      {
        $now['file'] = str_replace('\\', '/', $now['file']);

        if (false !== mb_strpos($now['file'], CONSOLE_PATH))
          $now['file'] = self::returnShortenFilePath('CONSOLE', $now['file']);
        elseif (false !== mb_strpos($now['file'], CORE_PATH))
          $now['file'] = self::returnShortenFilePath('CORE', $now['file']);
        elseif (false !== mb_strpos($now['file'], BASE_PATH))
          $now['file'] = self::returnShortenFilePath('BASE', $now['file']);
        else
          $compositeColoredPath = false;
      } else
      {
        $now['file'] = '';
        $compositeColoredPath = false;
      }

      echo CLI_LIGHT_BLUE, '| ', END_COLOR, str_pad(0 === $i ? (string) $exception->scode : '', self::TYPE_WIDTH - 1, ' '),
      self::consoleLine($now, 'function', self::FUNCTION_WIDTH),
      self::consoleLine($now, 'line', self::LINE_WIDTH),
        /** FILE - Path is shortened to the essential in order to leave more place for the path's end */
      self::consoleLine(
        $now,
        'file',
        // If the path is composite e.g. : 'KIND_OF_PATH + File'; then no coloring is needed
        $compositeColoredPath ? self::FILE_WIDTH + 37 : self::FILE_WIDTH,
        $now['file']
      ),
        /** ARGUMENTS */
      CLI_LIGHT_BLUE, '|', END_COLOR,
      ' NOT IMPLEMENTED YET',

      PHP_EOL;

      // echo $now['args']; after args has been converted
    }

    echo CLI_LIGHT_BLUE, '└' . str_repeat('─', self::TYPE_WIDTH)
      . '┴' . str_repeat('─', self::FUNCTION_WIDTH)
      . '┴' . str_repeat('─', self::LINE_WIDTH)
      . '┴' . str_repeat('─', self::FILE_WIDTH)
      . '┴' . str_repeat('─', self::ARGUMENTS_WIDTH), END_COLOR, PHP_EOL;
    // echo $this->context ...too big for console output !
  }

  /**
   * Returns the text that shows the headers for a unicode table (command line style)
   *
   * @param array $headers
   *
   * @return string
   */
  private static function consoleHeaders(array $headers) : string
  {
    $output = '';

    foreach($headers as &$value)
    {
      $output .= CLI_LIGHT_BLUE . '│' . CLI_YELLOW . str_pad(' ' . $value, constant('self::' . mb_strtoupper($value) .
      '_WIDTH'));
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
  private static function consoleLine(array $rowData, string $columnName, int $width, string $alternateContent = '') : string
  {
    return CLI_LIGHT_BLUE . '│' . END_COLOR .
      str_pad(true === isset($rowData[$columnName])
        ? ' ' . ('' === $alternateContent ? $rowData[$columnName] : $alternateContent) . ' '
        : ' -',
        $width
      );
  }
}

