<?
/**
 * Customized console exception class
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace lib\myLibs\console;

use lib\myLibs\LionelException;

class LionelExceptionCLI extends \Exception
{
  const TYPE_WIDTH = 21, // the longest type is E_RECOVERABLE_ERROR so 16 and we add 5 to this
    FUNCTION_WIDTH = 49,
    LINE_WIDTH = 9,
    FILE_WIDTH = 85,
    ARGUMENTS_WIDTH = 51;

  /**
   * Shows an exception 'colorful' display for command line commands.
   *
   * @param LionelException $exception
   */
  public static function showMessage(LionelException $exception)
  {
    echo redText(PHP_EOL . 'PHP exception' . PHP_EOL . '=============' . PHP_EOL . PHP_EOL);

    if (true === isset($exception->scode)) echo 'Error type ' , cyanText($exception->scode), ' in ' , cyanText($exception->file), ' at line ' , cyanText($exception->line), PHP_EOL, $exception->message, PHP_EOL;

    /******************************
     * Write HEADERS of the table *
     ******************************/
    echo PHP_EOL,
    lightBlueText('┌' . str_repeat('─', self::TYPE_WIDTH)
      . '┬' . str_repeat('─', self::FUNCTION_WIDTH)
      . '┬' . str_repeat('─', self::LINE_WIDTH)
      . '┬' . str_repeat('─', self::FILE_WIDTH)
      . '┬' . str_repeat('─', self::ARGUMENTS_WIDTH)), PHP_EOL,
    self::consoleHeaders(['Type', 'Function', 'Line', 'File', 'Arguments']),
    PHP_EOL,
    lightBlueText('├' . str_repeat('─', self::TYPE_WIDTH) .
      '┼' . str_repeat('─', self::FUNCTION_WIDTH) .
      '┼' . str_repeat('─', self::LINE_WIDTH) .
      '┼' . str_repeat('─',self::FILE_WIDTH) .
      '┼' . str_repeat('─',self::ARGUMENTS_WIDTH)), endColor(), PHP_EOL;

    /*******************************
     * Write the BODY of the table *
     *******************************/
    for($i = 0, $trace = $exception->backtraces, $max = count($trace); $i < $max; $i += 1)
    {
      $now = $trace[$i];

      if(0 === $i) unset($now['args']['variables']);

      convertArrayToShowableConsole($now['args'], 'Arguments', 'variables');

      if (isset($now['file']))
        $now['file'] = str_replace('\\', '/', $now['file']);

      echo lightBlueText('| '), str_pad(0 === $i ? (string) $exception->scode : '', self::TYPE_WIDTH - 1, ' '),
      self::consoleLine($now, 'function', self::FUNCTION_WIDTH),
      self::consoleLine($now, 'line', self::LINE_WIDTH),
        /** FILE - Path is shortened to the essential in order to leave more place for the path's end */
      self::consoleLine(
        $now,
        'file',
        self::FILE_WIDTH,
        true === isset($now['file'])
          ? (false === mb_strpos($now['file'], BASE_PATH)
          ? $now['file'] :
          mb_substr($now['file'], mb_strlen(BASE_PATH)))
          : ''
      ),
        /** ARGUMENTS */
      lightBlueText('|'),
      ' NOT IMPLEMENTED YET',

      PHP_EOL;

      // echo $now['args']; after args has been converted
    }

    echo lightBlueText('└' . str_repeat('─', self::TYPE_WIDTH)
      . '┴' . str_repeat('─', self::FUNCTION_WIDTH)
      . '┴' . str_repeat('─', self::LINE_WIDTH)
      . '┴' . str_repeat('─', self::FILE_WIDTH)
      . '┴' . str_repeat('─', self::ARGUMENTS_WIDTH)), PHP_EOL;
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
      $output .= lightBlueText('│') . brown() . str_pad(' ' . $value, constant('self::' . mb_strtoupper($value) . '_WIDTH'));
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
    return lightBlueText('│') .
      str_pad(true === isset($rowData[$columnName])
        ? ' ' . ('' === $alternateContent ? $rowData[$columnName] : $alternateContent) . ' '
        : ' -',
        $width
      );
  }
}
?>
