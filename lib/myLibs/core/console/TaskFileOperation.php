<?
define('PATTERN', '@\s{0,}
        (?:self::layout\(\);\s{0,})|
        (?:require(?:_once){0,1}\s[^;]{1,};\s{0,})|
        (?:extends\s[^\{]{1,})\s{0,}
        @mx');

/**
 * We have to manage differently the code that we put into an eval either it is PHP code or not
 *
 * @param string $contentToAdd
 */
function phpOrHTMLIntoEval(string &$contentToAdd)
{
  // Beginning of content
  $contentToAdd = ('<?' === substr($contentToAdd, 0, 2))
    ? substr($contentToAdd, 2)
    : '?>' . $contentToAdd;

  // Ending of content
  if ('?>' === substr($contentToAdd, - 2))
    $contentToAdd = substr($contentToAdd, 0, -2);
  else
    $contentToAdd .= '<?';
}

function hasSyntaxErrors(string $file) : bool
{
  exec('php -l ' . $file . ' 2>&1', $output); // Syntax verification, 2>&1 redirects stderr to stdout
  $sortie = implode(PHP_EOL, $output);

  if(strlen($sortie) > 6 && false !== strpos($sortie, 'pars', 7))
  {
    echo PHP_EOL, lightRed(), $sortie, PHP_EOL, PHP_EOL;
    require CORE_PATH . 'console/ConsoleTools.php';
    showContextByError($file, $sortie, 10);

    return true;
  }

  return false;
}

function compressPHPFile(string $fileToCompress, string $outputFile)
{
  // php_strip_whitespace doesn't not suppress double spaces in string and others. Beware of that rule, the preg_replace is dangerous !
//   file_put_contents($outputFile . '.php', rtrim(preg_replace('@\s+@', ' ', php_strip_whitespace($fileToCompress)) . "\n"));
  file_put_contents($outputFile . '.php', file_get_contents($fileToCompress));
  unlink($fileToCompress);
}

function contentToFile(string $content, string $outputFile)
{
  echo PHP_EOL, PHP_EOL, cyan(), 'FINAL CHECKINGS => ';
  /* Do not suppress the indented lines. They allow to test namespaces problems. We put the file in another directory
     in order to see if namespaces errors are declared at the normal place and not at the temporary place */
  $tempFile = '../logs/temporary file.php';
  file_put_contents($tempFile, $content);

  // Test each part of the process in order to precisely detect where there is an error.
  if (true === hasSyntaxErrors($tempFile))
  {
    echo PHP_EOL, PHP_EOL, lightRedText('[CLASSIC SYNTAX ERRORS in ' . substr($tempFile, strlen(BASE_PATH)) . '!]'), PHP_EOL;
    exit(1);
  }

  $smallOutputFile = substr($outputFile, strlen(BASE_PATH));

  echo lightGreen(), '[CLASSIC SYNTAX]';

  file_put_contents($outputFile, $content);

  if (true === hasSyntaxErrors($outputFile))
  {
    echo PHP_EOL, PHP_EOL, lightRedText('[NAMESPACES ERRORS in ' . $smallOutputFile . '!]'), PHP_EOL;
    exit(1);
  }

  echo lightGreen(), '[NAMESPACES]', PHP_EOL;
}

/**
 * @param int    $inc            Only for debugging purposes.
 * @param int    $level          Only for debugging purposes.
 * @param int    $nbFilesToInclude
 * @param string $finalContent   Actual content to be processed
 * @param string $contentToAdd   Actual content to be processed
 * @param array  $filesToConcat  Remaining files to concatenate
 * @param array  $alreadyMatched Files already added to the content
 *
 * @return bool False we break, true we continue
 */
function assembleFiles(int &$inc, int &$level, int &$nbFilesToInclude, string &$finalContent, string $contentToAdd, array &$filesToConcat, array &$alreadyMatched = [])
{
  preg_match_all(PATTERN, $contentToAdd, $matches);

  // We suppress the files routes.php and
//  foreach($matches as &$match)
//  {
//  CORE_PATH . 'Router.php'], $allFilesIncluded[BASE_PATH . 'config/Routes.php'
//  }

  // We don't search already searched things
  $tempMatches = array_diff(is_array($matches[0]) ? $matches[0] : $matches, $alreadyMatched);
  $numberMatches = count($tempMatches);

  ++$level;

  // If there are no more files to check we break the loop
  if (0 === $numberMatches)
  {
    --$level;
    return true;
  }

  // For all the inclusions
  foreach($tempMatches as &$match)
  {
    // If there are no more files to check we break the loop
    if(true === empty($filesToConcat))
      return true;

    $alreadyMatched[] = $match;

    $file = array_shift($filesToConcat);
    $contentToAdd = file_get_contents($file);

    // Show the actually parsed file
    if(1 === VERBOSE)
      echo str_repeat(' ', ($level) << 1) . '| ', substr($file, strlen(BASE_PATH)), PHP_EOL;

    --$nbFilesToInclude;

    // We increase the process step (DEBUG)
    ++$inc;

    // if it is a phtml template, we'll use an eval instead in order to include it exactly where we want
    if (false !== strpos($file, '.phtml'))
    {
      $posMatch = strpos($finalContent, $match);

      // if not found then $match has been trimmed in previous loops, so we retrieve $posMatch via the $match trimmed
      if (false === $posMatch)
        $posMatch = strpos($finalContent, preg_replace('@\s{2,}@', '', $match));

      // We escape the quotes only in the PHP portions of the file that we want to add
      $offset = 0;

      while(false !== ($posBeginPHP = strpos($contentToAdd, '<?', $offset)))
      {
        $posFinPHP = strpos($contentToAdd, '?>', $posBeginPHP);

        $contentToAdd = substr_replace($contentToAdd,
          str_replace('\'', '\\\'', substr($contentToAdd, $posBeginPHP, $posFinPHP - $posBeginPHP), $nbReplacements),
          $posBeginPHP,
          $posFinPHP - $posBeginPHP
        );

        $offset = $posBeginPHP + 2 + $nbReplacements;
      }

      /* We ensure us that the content have only one space after the PHP opening tag and before the PHP ending tag
       * that surrounds the require statement */
      $finalContent = preg_replace('@((?<=<\?)(\s){2,})|((\s){2,}(?=\?>))@', ' ', $finalContent);

      // We have to process the $match value because we have modified the code
      $newMatch = preg_replace('@\s{2,}@', '', $match);

      // Checks if our $match is in PHP code
      $inPhpTAG = preg_match('@(?<=<\?).*' . preg_quote($match) . '.*(?=\?>)@', $finalContent);

      // Checks if our $match is in an eval()
      $inEval = preg_match('@(?<=BOOTSTRAP###)[\S\s]*' . preg_quote($match) . '[\S\s]*(?=###BOOTSTRAP)@', $finalContent);

      if(1 === $inPhpTAG)
      {
        // We remove PHP tags
        substr_replace($finalContent, $newMatch, $posMatch - 2, $posMatch);

        $replacement = $contentToAdd;
      } else if(1 === $inEval)
      {
        phpOrHTMLIntoEval($contentToAdd);
        $replacement = 'eval(\\\'BOOTSTRAP###' . $contentToAdd . '###BOOTSTRAP\\\'); ';
      } else
      {
        phpOrHTMLIntoEval($contentToAdd);
        $replacement = 'eval(\'BOOTSTRAP###' . $contentToAdd . '###BOOTSTRAP\'); ';
      }

      // Add the code in the finalContent
      $finalContent = substr_replace(
        $finalContent,
        $replacement,
        strpos($finalContent, $newMatch),
        strlen($newMatch)
      );
    } else
      $finalContent = $contentToAdd . $finalContent;

    assembleFiles($inc, $level, $nbFilesToInclude, $finalContent, $contentToAdd, $filesToConcat, $alreadyMatched);

    // We increase the process step (DEBUG)
    ++$inc;
  }

  // We increase the process step (DEBUG)
  ++$inc;

  while (0 < $nbFilesToInclude)
  {
    // Either we begin the process, either we return to the roots of the process so ... (DEBUG)
    $level = 0;

    $file = array_shift($filesToConcat);

    if(1 === VERBOSE)
      echo PHP_EOL, substr($file, strlen(BASE_PATH)), PHP_EOL;

    $finalContent .= file_get_contents($file);
    --$nbFilesToInclude;

    assembleFiles(
      $inc,
      $level,
      $nbFilesToInclude,
      $finalContent,
      $finalContent,
      $filesToConcat,
      $alreadyMatched
    );
  }

  return true;
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
function fixFiles(&$content, &$verbose, &$filesToConcat = [])
{
  define('VERBOSE', (int) $verbose);

  if(false === empty($filesToConcat))
  {
    $nbFilesToInclude = count($filesToConcat);

    if(0 < $nbFilesToInclude && 1 === VERBOSE)
      echo PHP_EOL, PHP_EOL, cyanText(count($filesToConcat)), ' files to process ... ', PHP_EOL, PHP_EOL,
        substr(current($filesToConcat), strlen(BASE_PATH)), PHP_EOL;

    $content = file_get_contents(array_shift($filesToConcat));
    --$nbFilesToInclude;

    // we create these variables only for the reference pass
    $inc = $level = 0;
    $alreadyMatched = [];

    assembleFiles($inc, $level, $nbFilesToInclude, $content, $content, $filesToConcat, $alreadyMatched, []);

    echo PHP_EOL, str_pad('Files to include ', 71, '.', STR_PAD_RIGHT), greenText(' [LOADED]');
  }

  /**
   * We change things like \blabla\blabla\blabla::trial() by blabla::trial() and we include the related files
   */
  preg_match_all('@(\\\\){0,1}(([\\w]{1,}\\\\){1,})((\\w{1,})[:]{2}\\w{1,}\\()@', $content, $matches, PREG_OFFSET_CAPTURE);

  $temp = '';
  $newFilesUsed = [];
  $lengthAdjustment = 0;

  define('ERASE_SEQUENCE', "\033[1A\r\033[K\e[1A\r\e[K");

  // There are no files to add via direct static calls so we clean the console output
  if(1 === VERBOSE && false === empty($matches[0]))
    echo PHP_EOL, PHP_EOL, cyanText('------ STATIC DIRECT CALLS ------'), PHP_EOL;

  foreach($matches[0] as $key => $match)
  {
    $simpleFile = $matches[5][$key][0];
    $newFile = $matches[2][$key][0] . $simpleFile . '.php';

    // If we haven't already loaded that file and the class doesn't exist before this process, we add its content into $content
    if(false === in_array($newFile, $newFilesUsed)
      && false === strpos($content, 'class ' . $simpleFile)
      && false === strpos($temp, 'class ' . $simpleFile))
    {
      $newFilesUsed[] = $newFile;
      $temp .= file_get_contents(BASE_PATH . $newFile);

      if (1 === VERBOSE)
        echo PHP_EOL, str_pad($newFile . ' ', 71, '.', STR_PAD_RIGHT), greenText(' [LOADED]');
    }

    // We have to readjust the found offset each time with $lengthAdjustment 'cause we change the content length by removing content
    $length = strlen($match[0]);

    $content = substr_replace($content,
      $matches[4][$key][0],
      $match[1] - $lengthAdjustment,
      $length
    );

    // We calculate the new offset for the offset !
    $lengthAdjustment += $length - strlen($matches[4][$key][0]);
  }

  // Finally, there are no files to add via direct static calls so we clean the console output
  if (false === empty($matches[0]) && true === empty($newFilesUsed))
    echo ERASE_SEQUENCE;

  $content = $temp . $content;

  /**
   * We retrieves the "uses" (namespaces) and we use them to replace classes calls
   */
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

  /** We remove all the declare strict types declarations */
  $content = str_replace('declare(strict_types=1);', '', $content);

  /** We suppress namespaces */
  $content = preg_replace(
    '@\s*namespace [^;]{1,};\s*@',
    '',
    preg_replace('@\?>\s*<\?@', '', $content)
  );

  $content = preg_replace(
    '@(\\\\){0,1}(([\\w]{1,}\\\\){1,})(\\w{1,}[:]{2}\\w{1,}\\()@',
    '$4',
    $content
  );


  // We suppress our markers that helped us for the eval()
  $content = str_replace(['BOOTSTRAP###', '###BOOTSTRAP'], '', $content);

  /* We add the namespace cache\php at the beginning of the file
    then we delete final ... partial ... use statements taking care of not remove use in words as functions or comments like 'becaUSE'
 */

  return preg_replace(
    '@\buse\b@', '', ('<?' == substr($content, 0, 2))
    ? '<? declare(strict_types=1);' . PHP_EOL . 'namespace cache\php; ' . substr($content, 2)
    : '<? declare(strict_types=1);' . PHP_EOL . 'namespace cache\php; ?>' . $content
  );
}
?>
