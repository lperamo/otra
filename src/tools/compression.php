<?php
declare(strict_types=1);
namespace otra\tools;
/**
 * @author Lionel Péramo
 * @package otra\tools
 */

/**
 * GZIPs a file on disk. Based on function by Kioob at:
 * http://www.php.net/manual/en/function.gzwrite.php#34955
 *
 * @param string  $source      Path to file that should be compressed
 * @param ?string $destination Name of the file once gzipped. By default, we add 'gz' to the name.
 * @param int     $level       GZIP compression level (default: 9)
 * @param bool    $keep        Do we have to keep the source file
 *
 * @return bool Success or not
 */
function gzCompressFile(string $source, string $destination = null, int $level = 9, bool $keep = false) : bool
{
  $destination = $destination === null ? $source . '.gz' : $destination;

  $fp_out = gzopen($destination, 'wb' . ((string) $level));

  if ($fp_out === false)
    return false;

  $fp_in = fopen($source, 'rb');

  if ($fp_in === false)
  {
    gzclose($fp_out);
    return false;
  }

  while (!feof($fp_in))
    gzwrite($fp_out, fread($fp_in, 524288));  // 1024 * 512

  fclose($fp_in);
  gzclose($fp_out);

  // Avoids to keep a file without .gz extension and one with .gz extension for example.
  if (!$keep && $source !== $destination)
    unlink($source);

  return true;
}

