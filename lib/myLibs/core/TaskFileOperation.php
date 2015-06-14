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
  // fwrite($fp, rtrim(preg_replace('@\s+@', ' ', php_strip_whitespace($fileToCompress)) . "\n"));
  fwrite($fp, file_get_contents($fileToCompress));
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
 * Merges files and fixes the usage of namespaces and uses into the concatenated content
 *
 * @param $content       string Content to fix
 * @param $filesToConcat array  Files to merge
 *
 * @return mixed
 */
function fixUses($content, $filesToConcat = array())
{
  if(!empty($filesToConcat))
  {
    $nbHtmlsToInclude = count($filesToConcat) - 1;

    /* $tempContent wil not be equal to the real final content because we will suppress extends statement for this variable
       in order to not make researches of the same thing multiple times */
    $j = 0;
    if(0 < $nbHtmlsToInclude)
    {
     var_dump($filesToConcat, '**');
     echo 'Base file : ' , current($filesToConcat), PHP_EOL;
    }
    $tempContent = '';
    // echo $nbHtmlsToInclude, '*';

    // $finalContent = $tempContent;
    $finalContent = '';

    while(0 < $nbHtmlsToInclude)
    {
      $file = array_shift($filesToConcat);
      $finalContent .= ($contentToAdd = file_get_contents($file));
      $tempContent .= $contentToAdd;
      echo 'File under analysis : ', $j, ': ' , $file, PHP_EOL;
      // if($j == 4)
      //   die;
      // preg_match_all('@\s{0,}require(_once){0,1}\s([^;]{1,});\s{0,}@m', $content, $matches);
      $pattern = '@\s{0,}
        (?:require(?:_once){0,1}\s(?:[^;]{1,});)|
        (?:extends\s[^{]{1,}\{)
        \s{0,}@mx';
      $result = preg_match_all($pattern, $tempContent, $matches);

      // If the actual content doesn't have inclusions anymore, we add the next file and we retry
      if(!$result)
      {
        echo 'Nothing found.', PHP_EOL;
        // If there are no more files to check we break the loop
        if(empty($filesToConcat))
          break;

        continue;
        // $result = preg_match_all($pattern, $tempContent, $matches);
      }

      echo 'result: ', $result, ' ; RequirePos: ', strpos($tempContent, 'require'), '; ExtendsPos: ', strpos($tempContent, 'extends'), PHP_EOL;
  var_dump($matches);
      // unset($matches);
      $tempMatches = $matches[0];
      echo count($tempMatches) . ' fichiers a inclure trouves', PHP_EOL;

      foreach($tempMatches as $key => $match)
      {
        $file = array_shift($filesToConcat);
        echo '- ' . $file, PHP_EOL;
        $contentToAdd = file_get_contents($file);

        // If it's a class extends statement, we just put the file needed before the content
        if(false !== strpos($match, 'extends'))
        {
          $finalContent = $contentToAdd . $finalContent;
          $tempContent = $contentToAdd . $tempContent;
          $tempContent = substr_replace($tempContent, '', strpos($tempContent, $match), strlen($match));
        } else //if(false !== strpos($match, 'require'))
        {
          // If it's a file inclusion we replace the require statement by < ? content of the file ? >
          list($tempContent, $finalContent) = substr_replace(
            array($tempContent, $finalContent),
            '?>' . $contentToAdd . '<?',
            array(strpos($tempContent, $match), strpos($finalContent, $match)),
            strlen($match)
          );
        }
        echo $file . ' included.', PHP_EOL;
        $nbHtmlsToInclude--;
      }
      echo PHP_EOL;

      ++$j;
      // $content = $finalContent;
    }
    die;
    $content = $finalContent;
  }

  // Suppress all require calls 'cause we had already pre-included the files
  // preg_match_all('@\s{0,}require(_once){0,1}\s([^;]{1,});\s{0,}@m', $content, $matches);
// var_dump($matches);die;
  // foreach($matches[0] as $key => $require)
  // {
  //   $fileToInclude = eval('return ' . $matches[2][$key] . ';');
  //   echo $fileToInclude;
  //   // $nbHtmlsToInclude = count($htmlTpl);
  //   // echo $nbHtmlsToInclude, '*';
  //   // while(0 < $nbHtmlsToInclude)
  //   // {

  //   //   $nbHtmlsToInclude--;
  //   // }
  //   $content = str_replace($require, "\n", $content); // \n to prevent bugs with the other replacements
  // }

  $splits = explode(',', $content);
  $contents = array();

  // We retrieves the "uses" (namespaces) and we use them to replace classes calls
  preg_match_all('@^\s{0,}use[^;]{1,};\s{0,}$@m', $content, $matches);

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

  $content = preg_replace(
    '@\s*namespace [^;]{1,};\s*@',
    '',
    preg_replace('@\?>\s*<\?@', '', $content)
  );


  return str_replace('use ', '', ('<?' == substr($content, 0, 2))
    ? '<? namespace cache\php;' . substr($content, 2)
    : '<? namespace cache\php;?>' . $content
  );
}
?>
