<?php
/**
 * @author  Lionel Péramo
 * @package otra\tools\debug
 */
declare(strict_types=1);

namespace otra\tools\debug;

if (!function_exists(__NAMESPACE__ . '\\tailCustom'))
{
  /**
   * Slightly modified version of the original.
   * No verification of the fileDescriptor, we must check that before.
   *
   * @param string $filepath
   * @param int    $lines
   *
   * @return string
   * @link    http://stackoverflow.com/a/15025877/995958
   * @license http://creativecommons.org/licenses/by/3.0/
   * @author  Torleif Berger, Lorenzo Stanco, Lionel Péramo
   */
  function tailCustom(string $filepath, int $lines = 1) : string
  {
    $fileDescriptor = fopen($filepath, "rb");

    // Sets buffer size, according to the number of lines to retrieve.
    // This gives a performance boost when reading a few lines from the file.
    $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));

    // Jump to last character
    fseek($fileDescriptor, -1, SEEK_END);

    // Read it and adjust line number if necessary
    // (Otherwise the result would be wrong if file doesn't end with a blank line)
    if (fread($fileDescriptor, 1) !== PHP_EOL)
      --$lines;

    // Start reading
    $output = '';

    // While we would like more
    while (ftell($fileDescriptor) > 0 && $lines >= 0)
    {
      // Figure out how far back we should jump
      $seekOffset = min(ftell($fileDescriptor), $buffer);

      // Do the jump (backwards, relative to where we are)
      fseek($fileDescriptor, -$seekOffset, SEEK_CUR);

      // Read a chunk and prepend it to our output
      $output = ($chunk = fread($fileDescriptor, $seekOffset)) . $output;

      // Jump back to where we started reading
      fseek($fileDescriptor, -mb_strlen($chunk, '8bit'), SEEK_CUR);

      // Decrease our line counter
      $lines -= substr_count($chunk, PHP_EOL);
    }

    // Close file
    fclose($fileDescriptor);

    // While we have too many lines
    // (Because of buffer size we might have read too many)
    while ($lines++ < 0)
    {
      // Find first newline and remove all text before that
      $output = substr($output, strpos($output, PHP_EOL) + 1);
    }

    return trim($output);
  }
}
