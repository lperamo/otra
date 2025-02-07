<?php
declare(strict_types=1);
namespace otra\console;

use Exception;
use otra\OtraException;
use const otra\cache\php\{BASE_PATH, CONSOLE_PATH, CORE_PATH, DIR_SEPARATOR};

/**
 * Shows an exception 'colorful' display for command line commands.
 */
class OtraExceptionCli extends Exception
{
  private const array TABLE_COLUMNS = ['type', 'function', 'line', 'file', 'arguments'];

  private const int
    CELL_PADDING = 2,
    UNKNOWN_LENGTH = 7,
    LINE_HEADER_LENGTH = 4,
    NOT_IMPLEMENTED_YET_LENGTH = 19;

  private const string
    KEY_VARIABLES = 'variables',
    TOP_LEFT = '┌',
    TOP_SEP = '┬',
    TOP_RIGHT = '┐',
    MID_LEFT = '├',
    MID_SEP = '┼',
    MID_RIGHT = '┤',
    BOT_LEFT = '└',
    BOT_SEP = '┴',
    BOT_RIGHT = '┘',
    VERTICAL = '│',
    HORIZONTAL = '─';

  public function __construct(OtraException $exception)
  {
    parent::__construct();

    if (!empty($exception->context))
      unset($exception->context[self::KEY_VARIABLES]);

    $exception->backtraces = $exception->getTrace();

    // Is the error code a native error code?
    $exception->scode = OtraException::$codes[$exception->code] ?? 'UNKNOWN';
    $exception->message = preg_replace('/<br\s*\/?>/i', '', $exception->message);

    self::showMessage($exception);
  }

  /**
   * Returns a short path: e.g. "CORE_PATH + tools/cli.php" instead of the full absolute path
   */
  private static function returnShortenFilePath(string $pathType, string $file): string
  {
    return CLI_INFO_HIGHLIGHT . $pathType . '_PATH' . END_COLOR
      . ' + '
      . mb_substr($file, mb_strlen(constant('otra\\cache\\php\\' . $pathType . '_PATH')));
  }

  /**
   * Pads a string ignoring ANSI color codes.
   * Formula: length = $finalLength + (strlen($colored) - strlen($uncolored)).
   */
  public static function strPadIgnoreAnsi(
    string $content,
    int $finalLength,
    string $padString = ' ',
    int $padType = STR_PAD_RIGHT
  ): string
  {
    $uncolored = preg_replace('/\e\[[0-9;]*m/', '', $content);
    
    if ($uncolored === null)
      $uncolored = $content; // fallback

    return str_pad($content, $finalLength + strlen($content) - strlen($uncolored), $padString, $padType);
  }

  /**
   * Returns the maximum "uncolored" length of a set of strings (so we can align properly).
   */
  private static function getMaxUncoloredLength(array $strings): int
  {
    $maxLength = 0;

    foreach ($strings as $string)
    {
      if (!is_string($string))
        continue;
      
      $uncolored = preg_replace('/\e\[[0-9;]*m/', '', $string);
      
      if ($uncolored === null)
        $uncolored = $string;
      
      $length = mb_strlen($uncolored);
      
      if ($length > $maxLength)
        $maxLength = $length;
    }

    return $maxLength;
  }

  /**
   * Displays a single cell's content with a left and right padding ignoring ANSI.
   */
  private static function displayCell(string $content, int $width): string
  {
    // We add a space on each side so it looks a bit nicer
    return self::strPadIgnoreAnsi(' ' . $content . ' ', $width);
  }

  /**
   * Builds the top line (┌───┬───┬───┐), middle line (├───┼───┤), or bottom line (└───┴───┘) for the table.
   *
   * @param string $start The left char (e.g. '┌', '├', '└')
   * @param string $sep   The separator between columns (e.g. '┬', '┼', '┴')
   * @param string $end   The right char (e.g. '┐', '┤', '┘')
   * @param array<int> $widths The widths of each column
   *
   * @return string The line (without color, just the box-drawing chars).
   */
  private static function buildLine(string $start, string $sep, string $end, array $widths): string
  {
    $line = $start;

    $totalCols = count($widths);
    foreach ($widths as $index => $colWidth)
    {
      $line .= str_repeat(self::HORIZONTAL, $colWidth);
      // If it's not the last column, add the "sep"
      if ($index < $totalCols - 1)
        $line .= $sep;
    }

    $line .= $end;
    return $line;
  }

  /**
   * Actually displays the final message with the table, given the OtraException.
   */
  public static function showMessage(OtraException $exception): void
  {
    echo CLI_ERROR, PHP_EOL, 'PHP exception', PHP_EOL, '=============', END_COLOR, PHP_EOL, PHP_EOL;

    if (isset($exception->scode))
    {
      $filePath = $exception->file ?? '';

      // Replace known paths with constants
      $pathReplacements = [
        CONSOLE_PATH => 'CONSOLE_PATH + ',
        CORE_PATH => 'CORE_PATH + ',
        BASE_PATH => 'BASE_PATH + '
      ];

      foreach ($pathReplacements as $path => $replacement) {
        if (str_contains($filePath, $path)) {
          $filePath = str_replace($path, $replacement, $filePath);
          break;
        }
      }

      echo 'Error type ', CLI_INFO_HIGHLIGHT, $exception->scode, END_COLOR,
      ' in ', CLI_INFO_HIGHLIGHT, $filePath, END_COLOR,
      ' at line ', CLI_INFO_HIGHLIGHT, $exception->line, END_COLOR,
      PHP_EOL, $exception->message, PHP_EOL;
    }

    $backtraceData = $exception->backtraces;

    if (!empty($exception->context))
      array_push($backtraceData, ...$exception->context);

    // Preprocess backtrace entries
    foreach ($backtraceData as $traceIndex => $traceEntry)
    {
      if (empty($traceEntry['file']))
        continue;

      // Normalize and shorten file paths
      $normalizedPath = str_replace('\\', DIR_SEPARATOR, $traceEntry['file']);

      $pathShorteners = [
        CONSOLE_PATH => 'CONSOLE',
        CORE_PATH => 'CORE',
        BASE_PATH => 'BASE'
      ];

      foreach ($pathShorteners as $path => $prefix) {
        if (str_contains($normalizedPath, $path)) {
          $normalizedPath = self::returnShortenFilePath($prefix, $normalizedPath);
          break;
        }
      }

      $backtraceData[$traceIndex]['file'] = $normalizedPath;

      // Remove sensitive variables from args
      unset($backtraceData[$traceIndex]['args']['variables']);
    }

    // Calculate column widths
    $columnWidths = [
      max(mb_strlen($exception->scode), self::UNKNOWN_LENGTH) + self::CELL_PADDING,
      max(self::getMaxUncoloredLength(array_column($backtraceData, 'function')), 8) + self::CELL_PADDING,
      max(self::getMaxUncoloredLength(array_column($backtraceData, 'line')), self::LINE_HEADER_LENGTH) + self::CELL_PADDING,
      self::getMaxUncoloredLength(array_column($backtraceData, 'file')) + self::CELL_PADDING,
      max(self::UNKNOWN_LENGTH, self::NOT_IMPLEMENTED_YET_LENGTH) + self::CELL_PADDING
    ];

    // Build table output
    $output = CLI_TABLE
      . self::buildLine(self::TOP_LEFT, self::TOP_SEP, self::TOP_RIGHT, $columnWidths)
      . END_COLOR . PHP_EOL
      . CLI_TABLE;

    // Header row
    foreach (['Type', 'Function', 'Line', 'File', 'Arguments'] as $index => $header) {
      $output .= self::VERTICAL . CLI_TABLE_HEADER
        . self::displayCell($header, $columnWidths[$index])
        . CLI_TABLE;
    }

    $output .= self::VERTICAL . PHP_EOL . CLI_TABLE
      . self::buildLine(self::MID_LEFT, self::MID_SEP, self::MID_RIGHT, $columnWidths)
      . END_COLOR . PHP_EOL;

    // Backtrace rows
    foreach ($backtraceData as $traceIndex => $traceEntry)
    {
      $output .= CLI_TABLE . self::VERTICAL
        . self::strPadIgnoreAnsi(' ' . ($traceIndex === 0 ? $exception->scode : '') . ' ', $columnWidths[0])
        . self::VERTICAL
        . self::strPadIgnoreAnsi(' ' . ($traceEntry['function'] ?? '-') . ' ', $columnWidths[1])
        . self::VERTICAL
        . self::strPadIgnoreAnsi(' ' . (isset($traceEntry['line']) ? (string)$traceEntry['line'] : '-') . ' ', $columnWidths[2])
        . self::VERTICAL
        . self::strPadIgnoreAnsi(' ' . ($traceEntry['file'] ?? '-') . ' ', $columnWidths[3])
        . CLI_TABLE . self::VERTICAL
        . self::strPadIgnoreAnsi(' NOT IMPLEMENTED YET ', $columnWidths[4])
        . self::VERTICAL . END_COLOR . PHP_EOL;
    }

    $output .= CLI_TABLE
      . self::buildLine(self::BOT_LEFT, self::BOT_SEP, self::BOT_RIGHT, $columnWidths)
      . END_COLOR . PHP_EOL;

    echo $output;
  }
}
