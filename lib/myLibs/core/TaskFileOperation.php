<?
function hasSyntaxErrors($file_, $verbose)
{
  exec('php -l ' . $file_, $output); // Syntax verification
  $sortie = implode(' ', $output);

  if(strlen($sortie) > 6 && false !== strpos($sortie, 'pars', 7))
  {
    echo lightRed(), ' Beware of the syntax errors in the file ', $file_,' !', endColor(), PHP_EOL;
    if($verbose)
      echo $sortie;

    return true;
  }
  echo lightGreen(), ' No syntax errors.', endColor(), PHP_EOL;
}

function compressPHPFile($fileToCompress, $outputFile)
{
  $fp = fopen($outputFile . '.php', 'w');
  // php_strip_whitespace doesn't not suppress double spaces in string and others. Beware of that rule, the preg_replace is dangerous !
  fwrite($fp, rtrim(preg_replace('@\s+@', ' ', php_strip_whitespace($fileToCompress)) . "\n"));
  // fwrite($fp, file_get_contents($fileToCompress));
  fclose($fp);
  unlink($fileToCompress);
}

function contentToFile($content, $outputFile)
{
  $fp = fopen($outputFile, 'w');
  fwrite($fp, $content);
  fclose($fp);
}

/**
 * Corrects the usage of namespaces and uses into the concatenated content
 */
function fixUses($content)
{
  $splits = explode(',', $content);
  $contents = array();

  // We retrieves the "uses" (namespaces) and we use them to replace classes calls
  preg_match_all('@^\s{0,}use\s[^;]{1,};\s{0,}$@m', $content, $matches);

  foreach ($matches[0] as $match)
  {
    $matches2 = explode(',', $match);
    $matches2[0] = substr(trim($matches2[0]), 4);
    $lastKey = count($matches2) - 1;

    foreach($matches2 as $key => $match2)
    {
      $match2 = trim($match2);

      if($key == $lastKey)
        $match2 = substr($match2, 0, -1);

      $matchChunks = explode('\\', $match2);
      $classToReplace = array_pop($matchChunks);

      $needle = $match2 . (($key == $lastKey) ? ';' : ',');
      $pos = strpos($content, $needle);

      if(false !== $pos)
        $content = substr_replace($content, '', $pos, strlen($needle));

      if('Router' == $classToReplace || 'Routes' == $classToReplace)
        $content = preg_replace('@(?<!class )' . addslashes($match2) . '@', $classToReplace, $content);
    }
  }


  // We refactor the namespaces
  return str_replace('use ', '', '<? namespace cache\php;' .
    substr(preg_replace(
        '@\s*namespace [^;]{1,};\s*@',
        '',
        preg_replace('@\?>\s*<\?@', '', $content)
    ), 2));
}
?>
