<?php
declare(strict_types=1);
namespace otra\tools;
/**
 * @author Lionel Péramo
 * @package otra\tools
 */

/**
 * Compresses a file on disk using Brotli. 
 *
 * @param string  $sourceFile      Path to the file that should be compressed
 * @param ?string $destinationFile Name of the file once compressed. By default, '.br' is added to the name.
 * @param int     $level           Brotli compression level (default: 7, range: 0-11)
 * @param bool    $keep            Should the source file be kept after compression?
 *
 * @return bool Success or failure
 */

function brotliCompressFile(string $sourceFile, ?string $destinationFile = null, int $level = 7, bool $keep = false) : bool
{
  // Default destination to the source file with a .br extension
  $destinationFile ??= $sourceFile . '.br';
  $filePointerIn = fopen($sourceFile, 'rb');
  
  if ($filePointerIn === false)
    return false;

  $fpOut = fopen($destinationFile, 'wb');
  
  if ($fpOut === false)
  {
    fclose($filePointerIn);
    return false;
  }

  $context = brotli_compress_init($level);
  
  if ($context === false)
  {
    fclose($filePointerIn);
    fclose($fpOut);
    return false;
  }

  // Read and compress the file in chunks
  while (!feof($filePointerIn))
  {
    $chunk = fread($filePointerIn, 524_288); // 512 KB chunk

    if ($chunk === false)
    {
      fclose($filePointerIn);
      fclose($fpOut);
      return false;
    }

    fwrite($fpOut, brotli_compress_add($context, $chunk, BROTLI_PROCESS));
  }

  fwrite($fpOut, brotli_compress_add($context, '', BROTLI_FINISH));

  fclose($filePointerIn);
  fclose($fpOut);

  if (!$keep && $sourceFile !== $destinationFile)
    unlink($sourceFile);

  return true;
}

