<?
/**
 * Customized console exception class
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace lib\myLibs\console;

use lib\myLibs\Lionel_Exception;

class LionelExceptionCLI extends \Exception
{
  const TYPE_WIDTH = 16, // the longest type is E_RECOVERABLE_ERROR
    FUNCTION_WIDTH = 40,
    LINE_WIDTH = 4,
    FILE_WIDTH = 80;

  /**
   * Shows an exception 'colorful' display for command line commands.
   *
   * @param Lionel_Exception $exception
   */
  public static function showMessage(Lionel_Exception $exception)
  {
    echo redText(PHP_EOL . 'PHP exception' . PHP_EOL . '=============' . PHP_EOL . PHP_EOL);

    if (true === isset($exception->code)) echo 'Error type ' , cyanText($exception->code), ' in ' , cyanText($exception->file), ' at line ' , cyanText($exception->line), PHP_EOL, $exception->message, PHP_EOL;

    /******************************
     * Write HEADERS of the table *
     ******************************/
    echo PHP_EOL,
    lightBlueText(str_repeat('-', self::TYPE_WIDTH + self::FUNCTION_WIDTH + self::LINE_WIDTH + self::FILE_WIDTH + 80)), PHP_EOL,
    self::consoleHeader(' TYPE', self::TYPE_WIDTH),
    self::consoleHeader(' Function', self::FUNCTION_WIDTH),
    self::consoleHeader(' Line', self::LINE_WIDTH),
    self::consoleHeader(' File', self::FILE_WIDTH),
    self::consoleHeader(' Arguments', 0),
    PHP_EOL,
    lightBlueText(str_repeat('-', self::TYPE_WIDTH + self::FUNCTION_WIDTH + self::LINE_WIDTH + self::FILE_WIDTH + 80)), endColor(), PHP_EOL;

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

      echo lightBlueText('| '), str_pad(0 === $i ? $exception->code : '', self::TYPE_WIDTH + 4, ' '),
      self::consoleLine($now, 'function', self::FUNCTION_WIDTH + 9),
      self::consoleLine($now, 'line', self::LINE_WIDTH + 5),
        /** FILE - Path is shortened to the essential in order to leave more place for the path's end */
      self::consoleLine(
        $now,
        'file',
        self::FILE_WIDTH + 5,
        true === isset($now['file'])
          ? (false === strpos($now['file'], BASE_PATH)
          ? $now['file'] :
          substr($now['file'], strlen(BASE_PATH)))
          : ''
      ),
        /** ARGUMENTS */
      lightBlueText('|'),
      ' NOT IMPLEMENTED YET',

      PHP_EOL;

      // echo $now['args']; after args has been converted
    }

    echo lightBlueText(str_repeat('-', self::TYPE_WIDTH + self::FUNCTION_WIDTH + self::LINE_WIDTH + self::FILE_WIDTH + 80)), PHP_EOL;
    // echo $this->context ...too big for console output !
  }

  /**
   * Returns the content of a stack trace header in a console style.
   *
   * @param string $headerName
   * @param int    $width
   *
   * @return string
   */
  private static function consoleHeader(string $headerName, int $width) : string
  {
    return lightBlueText('|') . brown() . $headerName . str_repeat(' ', $width);
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
    return lightBlueText('|') .
    str_pad(true === isset($rowData[$columnName])
      ? ' ' . ('' === $alternateContent ? $rowData[$columnName] : $alternateContent) . ' '
      : ' -',
      $width
    );
  }
}
?>
