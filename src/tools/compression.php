<?php
declare(strict_types=1);
/**
 * GZIPs a file on disk. Based on function by Kioob at:
 * http://www.php.net/manual/en/function.gzwrite.php#34955
 *
 * @param string      $source Path to file that should be compressed
 * @param string|null $dest   Name of the file once gzipped. By default, we add 'gz' to the name.
 * @param int         $level  GZIP compression level (default: 9)
 * @param bool        $keep   Do we have to keep the source file
 *
 * @return bool Success or not
 */
function gzCompressFile(string $source, string $dest = null, int $level = 9, $keep = false) : bool
{
  $dest = $dest === null ? $source . '.gz' : $dest;

  $fp_out = gzopen($dest, 'wb' . $level);

  if ($fp_out === false)
    return false;

  $fp_in = fopen($source, 'rb');

  if ($fp_in === false)
  {
    gzclose($fp_out);
    return false;
  }

  while (feof($fp_in) === false)
    gzwrite($fp_out, fread($fp_in, 524288));  // 1024 * 512

  fclose($fp_in);
  gzclose($fp_out);

  // Avoids to keep a file without .gz extension and one with .gz extension for example.
  if ($keep === false && $source !== $dest)
    unlink($source);

  return true;
}

