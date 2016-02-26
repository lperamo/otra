<?
function hasSyntaxErrors(string $file_, bool $verbose) : bool
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

  return false;
}

function compressPHPFile(string $fileToCompress, string $outputFile)
{
  $fp = fopen($outputFile . '.php', 'w');
  // php_strip_whitespace doesn't not suppress double spaces in string and others. Beware of that rule, the preg_replace is dangerous !
  // fwrite($fp, rtrim(preg_replace('@\s+@', ' ', php_strip_whitespace($fileToCompress)) . "\n"));
  fwrite($fp, file_get_contents($fileToCompress));
  fclose($fp);
  unlink($fileToCompress);
}

function contentToFile(string $content, string $outputFile)
{
  /* Do not suppress the indented lines. They allow to test namespaces problems. We put the file in another directory
     in order to see if namespaces errors are declared at the normal place and not at the temporary place */
  $tempFile = '../logs/temporary file.php';
  file_put_contents($tempFile, $content);

  // Test each part of the process in order to precisely detect where there is an error.
  if (hasSyntaxErrors($tempFile, true))
    die(PHP_EOL . PHP_EOL . lightRedText('Syntax errors when putting content in ' . substr($tempFile, strlen(BASE_PATH)) . '!' . PHP_EOL));

  $smallOutputFile = substr($outputFile, strlen(BASE_PATH));
  $endText = ' syntax errors in ' . $smallOutputFile . endColor() . PHP_EOL;

  echo PHP_EOL, lightGreen(), 'No \'classic\'', $endText;

  $fp = fopen($outputFile, 'w');
  fwrite($fp, $content);
  fclose($fp);

  if (hasSyntaxErrors($outputFile, true))
    die(PHP_EOL . PHP_EOL . lightRedText('Syntax errors when putting content in the final file ' . $smallOutputFile . '!' . PHP_EOL));

  echo lightGreen(), 'No namespaces', $endText;
}

/**
 * @param string   $inc           Only for debugging purposes.
 * @param int      $nbHtmlsToInclude
 * @param string   $tempContent   Actual content to be processed
 * @param array    $filesToConcat Remaining files to concatenate
 * @param bool     $verbose       Verbose debugging
 * @param string   $pattern       Pattern to use for the search
 * @param bool|int $result        Numbers of results if already made
 * @param array    $matches       Results of the search of included files (via require) if already made
 *
 * @return bool True we break, true we continue
 */
function assembleFiles(int &$inc, int &$nbHtmlsToInclude, string &$tempContent, array &$filesToConcat, &$verbose, &$pattern, $result = [], array $matches = [])
{
  if(empty($result))
    $result = preg_match_all($pattern, $tempContent, $matches);

  if (!$result)
  {
    if ($verbose)
      echo cyan(), 'Nothing found. Only one file included.', endColor(), PHP_EOL;

    // If there are no more files to check we break the loop
    if (empty($filesToConcat))
      return $tempContent;
  }

  $tempMatches = is_array($matches[0]) ? $matches[0] : $matches;

  if($verbose)
    echo PHP_EOL, lightCyan(), count($tempMatches), cyan(), ' file(s) to include found.', endColor(); // in addition to the parsed file

  // For all the inclusions
  foreach($tempMatches as $match)
  {
    if(empty($filesToConcat))
      return $tempContent;

    $file = array_shift($filesToConcat);

    $contentToAdd = file_get_contents($file);

    if(13 == $inc)
    {
      file_put_contents('../logs/temporary file.php', $tempContent);
      die;
    }

    // If we have a require statement, we replace it by the file content
    if(false === strpos($match, 'extends'))
    {
      $posRequire = strpos($tempContent, $match);

      if ($posRequire) // why is this condition necessary ?
      {
        if(false !== strpos($match, 'self::layout'))
        {
          die('coucou');
          $tempContent = substr_replace($tempContent, ' ?>' . $contentToAdd . '<? ', $posRequire, strlen($match));
        } else
        {
          if (false === strpos($contentToAdd, '<?')) // if it's a phtml template with NO PHP
          {
            //$basePathLength = strlen(BASE_PATH);
            //$absoluteDir = substr($file, $basePathLength, strlen($file) - strlen(basename($file)) - $basePathLength - 1);
            //// we modify the require statement in order to replace __DIR__ by a path accessible from anywhere
            //$tempContent = substr_replace($tempContent, str_replace('__DIR__', '\'' . $absoluteDir . '\'', $match), $posRequire, strlen($match));
            $tempContent = substr_replace($tempContent, ' ?>' . $contentToAdd . '<? ', $posRequire, strlen($match));
          } else
          {
            if (false !== strpos($file, '.phtml')) // if it is a phtml template with PHP
            {
              // If it doesn't begin with a PHP tag
              if ('<?' !== substr($contentToAdd, 0, 2))
                $contentToAdd = '?> ' . $contentToAdd;

              // If it doesn't end with a PHP tag
              if ('?>' !== substr($contentToAdd, 0, strlen($contentToAdd) - 2))
                $contentToAdd .= ' <?';

              $tempContent = substr_replace($tempContent, $contentToAdd, $posRequire, strlen($match));

              /*$basePathLength = strlen(BASE_PATH);
              $absoluteDir = BASE_PATH . substr($file, $basePathLength, strlen($file) - strlen(basename($file)) - $basePathLength - 1);
              // we modify the require statement in order to replace __DIR__ by a path accessible from anywhere

              $tempContent = $contentToAdd . substr_replace($tempContent, str_replace('__DIR__', '\'' . $absoluteDir . '\'', $match), $posRequire, strlen($match));*/
            } else
              $tempContent = $contentToAdd . $tempContent;
          }
        }
      }
    } else // otherwise we just put the file content before
      $tempContent = $contentToAdd . $tempContent;

    if($verbose)
    {
      $tempFile = '../logs/temporary file.php';
      file_put_contents($tempFile, $tempContent);

      // Test each part of the process in order to precisely detect where there is an error.
      if (hasSyntaxErrors($tempFile, true))
      {
        echo PHP_EOL, PHP_EOL, lightRedText('----------- ERRORS -----------'), PHP_EOL, PHP_EOL;
        echo cyanText(str_pad('File', 17, ' ') . ' : '), substr($file, strlen(BASE_PATH)), PHP_EOL;
        echo cyanText('Require statement : '), preg_replace('@\s@', '', $match), PHP_EOL;
        echo cyanText(str_pad('Process turn', 17, ' ') . ' : '), $inc, PHP_EOL, PHP_EOL;
        echo lightRedText('------------------------------'), PHP_EOL;
        die;
      }

      echo lightGreen(), ' No syntax errors.', endColor(), PHP_EOL;

      echo substr($file, strlen(BASE_PATH)), ' included.', PHP_EOL;
    }

    --$nbHtmlsToInclude;
    ++$inc;

    if(! assembleFiles($inc, $nbHtmlsToInclude, $tempContent, $filesToConcat, $verbose, $pattern))
      break;
  }

  return $tempContent;
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
  if($verbose)
  {
    echo PHP_EOL, lightCyanText('Files to concat (' . count($filesToConcat) . ') :'), PHP_EOL, PHP_EOL;

    foreach ($filesToConcat as $file)
    {
      echo substr($file, strlen(BASE_PATH)), PHP_EOL, PHP_EOL;
    }

    echo PHP_EOL;
  }

  if(!empty($filesToConcat))
  {
    $nbHtmlsToInclude = count($filesToConcat) - 1;

    /* $tempContent wil not be equal to the real final content because we will suppress extends statement for this variable
       in order to not make researches of the same thing multiple times */
    $j = 0;

    if(0 < $nbHtmlsToInclude && $verbose)
     echo lightCyanText('Base file : '), substr(current($filesToConcat), strlen(BASE_PATH)), PHP_EOL;

    $finalContent = '';$tempContent = '';
    $inc = 0;

    while(0 < $nbHtmlsToInclude)
    {
      $file = array_shift($filesToConcat);
      $masterContentToAdd = file_get_contents($file);
      $tempContent .= $masterContentToAdd;

      if($verbose)
        echo PHP_EOL, lightCyanText('File under analysis : '), $j, ': ' , substr($file, strlen(BASE_PATH)), PHP_EOL;

      --$nbHtmlsToInclude;
      $pattern = '@\s{0,}
        (?:self::layout();\s{0,})|
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

      $finalContent = assembleFiles($inc, $nbHtmlsToInclude, $tempContent, $filesToConcat, $verbose, $pattern, $result, $matches[0]);

      if($verbose)
        echo PHP_EOL;

      ++$j;
    }

    $content = $finalContent;
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

  // We suppress namespaces
  $content = preg_replace(
    '@\s*namespace [^;]{1,};\s*@',
    '',
    preg_replace('@\?>\s*<\?@', '', $content)
  );

 /* We add the namespace cache\php at the beginning of the file
    then we delete final ... partial ... use statements,
    then we change things like \blabla\blabla\blabla::trial() by blabla::trial()
 */

  return preg_replace('@(\\\\){0,1}(([\\w]{1,}\\\\){1,})(\\w{1,}[:]{2}\\w{1,}\\()@', '$4', str_replace('use ', '', ('<?' == substr($content, 0, 2))
    ? '<? namespace cache\php;' . substr($content, 2)
    : '<? namespace cache\php;?>' . $content
  ));

  /*return str_replace('use ', '', ('<?' == substr($content, 0, 2))
    ? '<? namespace cache\php;' . substr($content, 2)
    : '<? namespace cache\php;?>' . $content
  );*/
}
?>
