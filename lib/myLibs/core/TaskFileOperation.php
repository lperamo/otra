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
 * @param $verbose       bool
 * @param $filesToConcat array  Files to merge
 *
 * @return mixed
 */
function fixUses($content, $verbose, $filesToConcat = [])
{
//echo '<pre>';
//var_dump($filesToConcat);
//  echo PHP_EOL, PHP_EOL;
//echo '</pre>';

  if(!empty($filesToConcat))
  {
    $nbHtmlsToInclude = count($filesToConcat) - 1;

    /* $tempContent wil not be equal to the real final content because we will suppress extends statement for this variable
       in order to not make researches of the same thing multiple times */
    $j = 0;
    if(0 < $nbHtmlsToInclude && $verbose)
     echo 'Base file : ' , current($filesToConcat), PHP_EOL;

    $finalContent = '';$tempContent = '';

    while(0 < $nbHtmlsToInclude)
    {
      $file = array_shift($filesToConcat);
      //echo '++++', $file, $nbHtmlsToInclude, '*****', PHP_EOL;
      $masterContentToAdd = file_get_contents($file);
      $tempContent .= $masterContentToAdd;

      if($verbose)
        echo 'File under analysis : ', $j, ': ' , $file, PHP_EOL;

      $nbHtmlsToInclude--;
      $pattern = '@\s{0,}
        (?:require(?:_once){0,1}\s[^;]{1,};\s{0,})|
        (?:extends\s[^\{]{1,})\s{0,}
        @mx';
      $result = preg_match_all($pattern, $tempContent, $matches);

      // If the actual content doesn't have inclusions anymore, we add the next file and we retry
      if(!$result)
      {
        if($verbose)
          echo 'Nothing found. Only one file included.', PHP_EOL;

        $finalContent .= $masterContentToAdd;
        // If there are no more files to check we break the loop
        if(empty($filesToConcat))
          break;

        continue;
      }

      if($verbose)
        echo 'result: ', $result, ' ; RequirePos: ', strpos($tempContent, 'require'), '; ExtendsPos: ', strpos($tempContent, 'extends'), PHP_EOL;

      $tempMatches = $matches[0];

      if($verbose)
        echo count($tempMatches) . ' file(s) to include found (in addition to the parsed file)', PHP_EOL;

      foreach($tempMatches as $match)
      {
        $file = array_shift($filesToConcat);

        if($verbose)
          echo '- ' . $file, PHP_EOL;

        $contentToAdd = file_get_contents($file);

        // We just put the file needed before the content in any case
        $finalContent = $contentToAdd . $finalContent;
        $tempContent = $contentToAdd . $tempContent ;

        if(false === strpos($match, 'extends'))
          $tempContent = substr_replace($tempContent, '', strpos($tempContent, $match), strlen($match));
        //$finalContent = substr_replace($finalContent, '', strpos($finalContent, $match), strlen($match));

        //contentToFile($tempContent, 'tempcontent.php');
        //contentToFile($finalContent, 'finalContent.php');

        if($verbose)
          echo $file . ' included.', PHP_EOL;

        $nbHtmlsToInclude--;
      }

      //$tempContent .= $masterContentToAdd;
      $finalContent .= $masterContentToAdd;

      if($verbose)
        echo PHP_EOL;

      ++$j;
    }

    //$content = $finalContent;
    $content = $tempContent;
  }

  $splits = explode(',', $content);
  $contents = [];

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

      $needle = $match2 . ($key == $lastKey ? ';' : ',');
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

if(!empty($filesToConcat))
{
  contentToFile($content, 'tempcontent.php');
}

  /*if(!empty($filesToConcat))
  {
    contentToFile(str_replace('use ', '', ('<?' == substr($content, 0, 2))
      ? '<? namespace cache\php;' . substr($content, 2)
      : '<? namespace cache\php;?>' . $content
    ), 'tempcontent.php');
  }*/


 /* We add the namespace cache\php at the beginning of the file
    then we delete use statements,
    then we change things like \blabla\blabla\blabla::trial() by blabla::trial()
 */

  return preg_replace('@(\\\\){0,1}(([\\w]{1,}\\\\){1,})(\\w{1,}[:]{2}\\w{1,}\\()@', '$4', str_replace('use ', '', ('<?' == substr($content, 0, 2))
    ? '<? namespace cache\php;' . substr($content, 2)
    : '<? namespace cache\php;?>' . $content
  ));
}
?>
