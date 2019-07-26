<?
define('PATTERN', '@\s{0,}
        (?:(?<!//\\s)require(?:_once){0,1}\s[^;]{1,};\s{0,})|
        (?:(?<!//\\s)extends\s[^\{]{1,}\s{0,})|
        (?:->renderView\s{0,}\([^\),]{1,})
        @mx');
// a previous line in first position (we don't include it for now because the templates management is not optimal yet)=> (?:(?<!//\\s)self::layout\(\);\s{0,})|

define('ANNOTATION_DEBUG_PAD', 80);
define('LOADED_DEBUG_PAD', 80);

/**
 * We have to manage differently the code that we put into an eval either it is PHP code or not
 *
 * @param string $contentToAdd
 *
 * @return bool
 */
function phpOrHTMLIntoEval(string &$contentToAdd)
{
  // Beginning of content
  $contentToAdd = '<?' === substr($contentToAdd, 0, 2)
    ? substr($contentToAdd, 2)
    : '?>' . $contentToAdd;

  // Ending of content
  if ('?>' === substr($contentToAdd, - 2))
    $contentToAdd = substr($contentToAdd, 0, -2);
  else
    $contentToAdd .= '<?';

  return '<?' === substr($contentToAdd, 0, 2);
}

/**
 * @param string $file
 *
 * @return bool
 */
function hasSyntaxErrors(string $file) : bool
{
  exec(PHP_BINARY . ' -l ' . $file . ' 2>&1', $output); // Syntax verification, 2>&1 redirects stderr to stdout
  $output = implode(PHP_EOL, $output);

  if (strlen($output) > 6 && false !== strpos($output, 'pars', 7))
  {
    echo PHP_EOL, CLI_LIGHT_RED, $output, PHP_EOL, PHP_EOL;
    require CORE_PATH . 'console/Tools.php';
    showContextByError($file, $output, 10);

    return true;
  }

  return false;
}

/**
 * @param string $fileToCompress
 * @param string $outputFile
 */
function compressPHPFile(string $fileToCompress, string $outputFile)
{
  // php_strip_whitespace doesn't not suppress double spaces in string and others. Beware of that rule, the preg_replace is dangerous !
  $contentToCompress = rtrim(preg_replace('@\s{1,}@', ' ', php_strip_whitespace($fileToCompress)) . PHP_EOL);

  file_put_contents(
    $outputFile . '.php',
    preg_replace('@;\s(class\s[^\s]{1,}) { @', ';$1{', $contentToCompress, -1, $count)
  );
  unlink($fileToCompress);
}

/**
 * @param string $content
 * @param string $outputFile
 */
function contentToFile(string $content, string $outputFile)
{
  echo PHP_EOL, PHP_EOL, CLI_CYAN, 'FINAL CHECKINGS => ';
  /* Do not suppress the indented lines. They allow to test namespaces problems. We put the file in another directory
     in order to see if namespaces errors are declared at the normal place and not at the temporary place */
  $tempFile = BASE_PATH . 'logs/temporary file.php';
  file_put_contents($tempFile, $content);

  // Test each part of the process in order to precisely detect where there is an error.
  if (true === hasSyntaxErrors($tempFile))
  {
    echo PHP_EOL, PHP_EOL, CLI_LIGHT_RED, '[CLASSIC SYNTAX ERRORS in ' . substr($tempFile, strlen(BASE_PATH)) . '!]',
      END_COLOR, PHP_EOL;
    exit(1);
  }

  $smallOutputFile = substr($outputFile, strlen(BASE_PATH));

  echo CLI_LIGHT_GREEN, '[CLASSIC SYNTAX]';

  file_put_contents($outputFile, $content);

  if (true === hasSyntaxErrors($outputFile))
  {
    echo PHP_EOL, PHP_EOL, CLI_LIGHT_RED, '[NAMESPACES ERRORS in ' . $smallOutputFile . '!]', END_COLOR, PHP_EOL;
    exit(1);
  }

  echo CLI_LIGHT_GREEN, '[NAMESPACES]', PHP_EOL;
}

/**
 * We analyze the use statement in order to retrieve the name of each class which is included in it.
 *
 * @param int    $level
 * @param array  $filesToConcat Files to parse after have parsed this one
 * @param string $class
 * @param array  $parsedFiles   Remaining files to concatenate
 */
function analyzeUseToken(int $level, array &$filesToConcat, string $class, array &$parsedFiles)
{
  $class = trim($class);

  // this class is already included in all the cases, no need to have it more than once !
  if ('config\\Router' === $class)
    return;

  // dealing with / at the beginning of the use
  if (false === isset(CLASSMAP[$class]))
  {
    if ('/' === substr($class, 1))
      $class = substr($class, 1);
    else
    {
      // It can be a SwiftMailer class for example.
      /**
       * TODO We have to manage the case where we write the use statement on multiple lines because of factorisation style like
       * use test/
       * {
       *  class/test,
       *  class/test2
       * } */
      echo CLI_BROWN, 'EXTERNAL LIBRARY CLASS : ' . $class, END_COLOR, PHP_EOL;
      return ;
    }
  }

  $tempFile = CLASSMAP[$class];

  // We add the file found in the use statement only if we don't have it yet
  if (false === in_array($tempFile, $parsedFiles))
  {
    $filesToConcat['php']['use'][] = $tempFile;
    $parsedFiles[] = $tempFile;
  } else if (1 < VERBOSE)
    showFile($level, $tempFile, ' ALREADY PARSED');
}

/**
 * We retrieve file names to include that are found via the use statements to $filesToConcat in php/use category.
 * We then clean the use keywords ...
 *
 * @param int    $level
 * @param string $contentToAdd  Content actually parsed
 * @param array  $filesToConcat Files to parse after have parsed this one
 * @param array  $parsedFiles   Remaining files to concatenate
 *
 * @return array $classesFromFile
 */
function getFileNamesFromUses(int $level, string &$contentToAdd, array &$filesToConcat, array &$parsedFiles) : array
{
  preg_match_all('@^\\s{0,}use\\s{1,}([^;]{0,});\\s{0,}@mx', $contentToAdd, $useMatches, PREG_OFFSET_CAPTURE);

  $classesFromFile = [];

  foreach($useMatches[1] as &$useMatch)
  {
    $chunks = explode(',', $useMatch[0]);
    $beginString = '';

    foreach ($chunks as &$chunk)
    {
      $chunk = trim($chunk);
      $posLeftParenthesis = strpos($chunk, '{');

      if (false !== $posLeftParenthesis) // case use xxx/xxx{XXX, xxx, xxx}; (notice the uppercase, it's where we are)
      {
        $beginString = substr($chunk, 0, $posLeftParenthesis); // like lib\myLibs\bdd\
        $lastChunk = substr($chunk, $posLeftParenthesis + 1); // like Sql
        $classToReplace = $beginString . str_replace(' ', '', $lastChunk);

        // simplifies the usage of classes by passing from FQCN to class name ... lib\myLibs\bdd\Sql => Sql
        str_replace($classToReplace, $lastChunk, $contentToAdd);

        // We analyze the use statement in order to retrieve the name of each class which is included in it.
        analyzeUseToken($level, $filesToConcat, $classToReplace, $parsedFiles);
      } else
      {
        /** if we have a right parenthesis we strip it and put the content before the beginning of the use statement,
         * /*  otherwise ... if it uses a PHP7 shortcut with parenthesis, we add the beginning of the use statement
         * /*  otherwise ... we just put the string directly */

        if (false === strrpos($chunk, '}'))
        {
          if ('' === $beginString) // case use xxx/xxx/xxx;
          {
            $classToReplace = $chunk;
            $tempChunks = explode('\\', $classToReplace);

            // simplifies the usage of classes by passing from FQCN to class name ... lib\myLibs\bdd\Sql => Sql
            str_replace($classToReplace, array_pop($tempChunks), $contentToAdd);
          } else // case use xxx/xxx{xxx, XXX, xxx}; (notice the uppercase, it's where we are)
          {
            $classToReplace = $beginString . $chunk;

            // simplifies the usage of classes by passing from FQCN to class name ... lib\myLibs\bdd\Sql => Sql
            str_replace($classToReplace, $chunk, $contentToAdd);
          }
        } else { // case use xxx/xxx{xxx, xxx, XXX}; (notice the uppercase, it's where we are)
          $lastChunk = substr($chunk, 0, -1);
          $classToReplace = $beginString . $lastChunk;

          // simplifies the usage of classes by passing from FQCN to class name ... lib\myLibs\bdd\Sql => Sql
          str_replace($classToReplace, $lastChunk, $contentToAdd);
        }

        // We analyze the use statement in order to retrieve the name of each class which is included in it.
        analyzeUseToken($level, $filesToConcat, $classToReplace, $parsedFiles);
      }

      // The classes will be useful when we will analyze the extends statements
      $classesFromFile[] = $classToReplace;

      // Now that we have retrieved the files to include, we can clean all the use statements
      // i need the modifier m in order to make the ^ work as expected
      $contentToAdd = preg_replace('@^use [^;]{1,};@m', '', $contentToAdd, -1, $count);
    }
  }

  return $classesFromFile;
}

/**
 * We test if we have dynamic variables into the require/include statement, and replace them if they exists
 *
 * @param string $tempFile
 * @param string $file
 * @param string $trimmedMatch
 *
 * @return array $isTemplate
 */
function evalPathVariables(string &$tempFile, string $file, string &$trimmedMatch) : array
{
  // no path variables found
  if (false === strpos($trimmedMatch, '$'))
    return [$tempFile, false];

  // the flag is necessary in order to validate correctly the next condition (with empty($pathVariables) I mean)
  preg_match_all('@\\$([^\\s\\)\\(]+)@', $tempFile, $pathVariables);
  $isTemplate = false;

  if (false === empty($pathVariables))
  {
    // we don't need the complete mask
    unset($pathVariables[0]);

    foreach($pathVariables as &$pathVariable)
    {
      if (true === isset(PATH_CONSTANTS[$pathVariable[0]]))
        $tempFile = str_replace('$' . $pathVariable[0], '\'' . PATH_CONSTANTS[$pathVariable[0]] . '\'', $tempFile);
      else
      {
        /* we make an exception for this particular require statement because
           it is a require made by the prod controller and then it is a template ...(so no need to include it, for now) */
        if ('templateFilename' === trim($pathVariable[0]))
          $isTemplate = true;
        else
        {
          echo CLI_RED, 'CANNOT EVALUATE THE REQUIRE STATEMENT BECAUSE OF THE NON DEFINED DYNAMIC VARIABLE ', CLI_BROWN,
            '$', $pathVariable[0], CLI_RED, ' in ', CLI_BROWN, $trimmedMatch, CLI_RED, ' in the file ', CLI_BROWN,
            $file, CLI_RED, ' !', END_COLOR, PHP_EOL;
          exit(1);
        }
      }
    }
  }

  return [$tempFile, $isTemplate];
}

/**
 * Shows the file name in the console for debug purposes
 *
 * @param int    $level
 * @param string $file
 * @param string $otherText
 */
function showFile(int &$level, string &$file, string $otherText = ' first file')
{
  if (0 < VERBOSE)
    echo str_pad(
      str_repeat(' ', $level << 1) . (0 !== $level ? '| ' : '') . substr($file, BASE_PATH_LENGTH),
      ANNOTATION_DEBUG_PAD,
      '.',
      STR_PAD_RIGHT
    ), CLI_BROWN, $otherText, END_COLOR, PHP_EOL;
}

/**
 * We escape the quotes only in the PHP portions of the file that we want to add
 *
 * @param string $contentToAdd
 */
function escapeQuotesInPhpParts(string &$contentToAdd)
{
  $offset = 0;

  while (false !== ($posBeginPHP = strpos($contentToAdd, '<?', $offset)))
  {
    $posFinPHP = strpos($contentToAdd, '?>', $posBeginPHP);

    $contentToAdd = substr_replace($contentToAdd,
      str_replace('\'', '\\\'', substr($contentToAdd, $posBeginPHP, $posFinPHP - $posBeginPHP), $nbReplacements),
      $posBeginPHP,
      $posFinPHP - $posBeginPHP
    );

    $offset = $posBeginPHP + 2 + $nbReplacements;
  }
}

/**
 * Extracts the file name and the 'isTemplate' information from the require/include statement.
 *
 * @param string $trimmedMatch
 * @param string $file
 *
 * @return array [$file, $isTemplate]
 */
function getFileInfoFromRequireMatch(string &$trimmedMatch, string &$file) : array
{
  // Extracts the file name in the require/include statement ...
  preg_match('@(?:require|include)(?:_once)?\s*([^;]+)\\)?@m', $trimmedMatch, $inclusionMatches);
  $tempFile = $inclusionMatches[1];

  /* We checks if the require/include statement is in a function call
     by counting the number of parenthesis between the require statement and the semicolon */
  if (0 !== substr_count($trimmedMatch, ')') % 2 )
    $tempFile = substr($tempFile, 0, -1);

  return evalPathVariables($tempFile, $file, $trimmedMatch);
}

/**
 * Process the template content and adds it to the final content.
 *
 * @param string $finalContent
 * @param string $contentToAdd Content actually parsed
 * @param string $match
 * @param int    $posMatch
 *
 * @return string $finalContent
 */
function processTemplate(string &$finalContent, string &$contentToAdd, string &$match, int &$posMatch) : string
{
  escapeQuotesInPhpParts($contentToAdd);

  /* We ensure us that the content have only one space after the PHP opening tag and before the PHP ending tag
   * that surrounds the require statement */
  $finalContent = preg_replace('@((?<=<\?)(\s){2,})|((\s){2,}(?=\?>))@', ' ', $finalContent);

  // We have to process the $match value because we have modified the code
  $newMatch = preg_replace('@\s{2,}@', '', $match);

  // Checks if our $match is in an eval()
  $inEval = preg_match('@(?<=BOOTSTRAP###)[\S\s]*' . preg_quote($match) . '[\S\s]*(?=###BOOTSTRAP)@', $finalContent);

  /* If our $match is in PHP code then we determine the replacement string according to the context */
  if(1 === preg_match('@(?<=<\?).*' . preg_quote($match) . '.*(?=\?>)@', $finalContent))
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

  // Adds the code in the finalContent
  return substr_replace(
    $finalContent,
    $replacement,
    strpos($finalContent, $newMatch),
    strlen($newMatch)
  );
}

/**
 * Process the return in the PHP content from $contentToAdd and include it directly in the $finalContent.
 *
 * @param string $finalContent
 * @param string $contentToAdd
 * @param string $match
 * @param int    $posMatch
 */
function processReturn(string &$finalContent, string &$contentToAdd, string &$match, int &$posMatch)
{
  $contentToAdd = trim(substr($contentToAdd, 9, -2)); // 9 means after the 'return' statement

  if (false !== strpos($match, 'require') &&
    0 !== substr_count($match, ')') % 2 &&
    ';' === substr($contentToAdd, -4, 1)) // We are looking for the only semicolon in the compiled 'Routes.php' file
  {
    $contentToAdd = substr_replace($contentToAdd, '', -4);
    $lengthToChange = strrpos($match, ')');
  } else
  {
    $lengthToChange = strlen($match);
  }

  // We remove the requires like statements before adding the content
  $finalContent = substr_replace($finalContent, $contentToAdd, $posMatch, $lengthToChange);
}

/**
 * @param array  $classesFromFile
 * @param string $class
 * @param string $contentToAdd
 * @param string $match
 *
 * @return string $tempFile
 */
function searchForClass(array &$classesFromFile, string &$class, string &$contentToAdd, string &$match)
{
  // Do we already have this class ?
  foreach($classesFromFile as &$classFromFile)
  {
    if (false !== strrpos($classFromFile, $class, strrpos($classFromFile,',')))
      return false;
  }

  // if it's not the case ... we search it in the directory of the file that we are parsing

  // /!\ BEWARE ! Maybe we don't have handled the case where the word namespace is in a comment.

  // we use a namespace so ...

  // we search the namespace in the content before the extends call
  $pos = strrpos($contentToAdd, 'namespace', $match - strlen($contentToAdd));

  // then we find the section that interests us
  $tempContent = substr($contentToAdd, $pos, $match - $pos);

  // then we can easily extract the namespace (10 = strlen('namespace'))
  // and concatenates it with a '\' and the class to get our file name
  $newClass = substr($tempContent, 10, strpos($tempContent, ';') - 10) . '\\' . $class;

  if (isset(CLASSMAP[$newClass]) === false)
  {
    echo CLI_BROWN, 'Notice : Please check if you use a class ', CLI_CYAN, $class, CLI_BROWN, ' in this file that you don\'t include and fix it ! Maybe it\'s only in a comment though.', END_COLOR, PHP_EOL;

    return false;
  }

  return CLASSMAP[$newClass];
}

/**
 * @param string $contentToAdd    Content actually parsed
 * @param string $file            Name of the file actually parsed
 * @param array  $filesToConcat   Files to parse after have parsed this one
 * @param array  $parsedFiles     Remaining files to concatenate
 * @param array  $classesFromFile Classes that we have retrieved from the previous analysis of use statements
 *                                (useful only for extends statements)
 */
function getFileInfoFromRequiresAndExtends(string &$contentToAdd, string &$file, array &$filesToConcat, array &$parsedFiles, array $classesFromFile)
{
  preg_match_all(PATTERN, $contentToAdd, $matches, PREG_OFFSET_CAPTURE);

  // For all the inclusions
  foreach($matches[0] as &$match)
  {
    if ('' === $match[0]) // TODO CAN WE SUPPRESS THIS CONDITION BY IMPROVING THE REGEXP ?
      continue;

    $trimmedMatch = trim(preg_replace('@\s{1,}@', ' ', $match[0]));

    /** WE RETRIEVE THE CONTENT TO PROCESS, NO TRANSFORMATIONS HERE */

    /** REQUIRE OR INCLUDE STATEMENT EVALUATION */
    if (false !== strpos($trimmedMatch, 'require'))
    {
      list($tempFile, $isTemplate) = getFileInfoFromRequireMatch($trimmedMatch, $file);

      /* we make an exception for this particular require statement because
         it is a require made by the prod controller and then it is a template ...
         (so no need to include it because html templates management is not totally functional right now) */
      if (false === $isTemplate) // if the file to include is not a template
      {
        // If we find __DIR__ in the include/require statement then we replace it with the good folder and not the actual folder (...console ^^)
        $posDir = strpos($tempFile, '__DIR__ .');

        if ($posDir !== false)
          $tempFile = substr_replace('__DIR__ . ', '\'' . dirname($file) . '/' . basename(substr($tempFile, $posDir, -1)) . '\'', $posDir, 9);

        // str_replace to ensure us that the same character '/' is used each time
        $tempFile = str_replace('\\', '/', eval('return ' . $tempFile . ';'));

        if (VERBOSE > 0 && strpos($tempFile, BASE_PATH) === false)
          echo PHP_EOL, CLI_BROWN, 'BEWARE, you have to use absolute path for files inclusion ! \'' . $tempFile, '\' in ',
          $file, END_COLOR, PHP_EOL;

        if (false === file_exists($tempFile))
        {
          echo PHP_EOL, CLI_RED, 'There is a problem with ', CLI_BROWN, $trimmedMatch, CLI_RED, ' => ', CLI_BROWN,
          $tempFile,
          CLI_RED
          , ' in ', CLI_BROWN, $file, CLI_RED, ' !', END_COLOR, PHP_EOL, PHP_EOL;
          exit(1);
        }

        if (true === in_array($tempFile, $parsedFiles, true))
          continue;

        $filesToConcat['php']['require'][$tempFile] = [
          'match' => $match[0],
          'posMatch' => strpos($contentToAdd, $match[0])
        ];
      }
    } else if(false !== strpos($trimmedMatch, 'extends')) /** EXTENDS */
    {
      // Extracts the file name in the extends statement ... (8 = strlen('extends '))
      $class = substr($trimmedMatch, 8);

      // if the class begin by \ then it is a standard class and then we do nothing otherwise we do this ...
      if ('\\' !== $class[0])
        $tempFile = searchForClass($classesFromFile, $class, $contentToAdd, $match[1]);
//      else if (true === in_array($tempFile, $parsedFiles)) // if we already have this class
//        $tempFile = false;
      else
        $tempFile = false;

      // If we already have included the class
      if (false === $tempFile)
        continue;

      $filesToConcat['php']['extends'][] = $tempFile;
    } else if(false === strpos($file, 'prod/Controller.php')) /** TEMPLATE via framework 'renderView' (and not containing method signature)*/
    {
      $trimmedMatch = substr($trimmedMatch, strpos($trimmedMatch, '(') + 1);

      // If the template file parameter supplied for renderView method is just a string
      if ($trimmedMatch[0] === '\'')
      {
        $trimmedMatch = substr($trimmedMatch, 1, -1);
      } else // More complicated...
      {
        /** TODO Maybe a case then with an expression, variable or something */
      }

      $tempDir = '';

      if (file_exists($trimmedMatch) === false)
      {
        $tempDir = str_replace('\\', '/', dirname($file));

        if (file_exists($tempDir . $trimmedMatch) === false)
        {
          // Retrieves the last directory name which is (maybe) the specific controller directory name which we will use as a view directory name instead
          $tempDir = realpath($tempDir . '/../../views' . substr($tempDir, strrpos($tempDir, '/'))) . '/';

          if (file_exists($tempDir . $trimmedMatch) === false)
          {
            if ($trimmedMatch === '/exception.phtml')
              $tempDir = CORE_PATH . 'views/' ;
            else // no ? so where is that file ?
            {
              if (strpos($trimmedMatch, 'html') === false)
              {
                echo CLI_RED, '/!\\ We cannot find the file ', CLI_BROWN, $trimmedMatch, CLI_RED, ' seen in ' .
                  CLI_BROWN,
                $file,
                CLI_RED, '. ', PHP_EOL, 'Please fix this and try again.', PHP_EOL, END_COLOR;
                die;
              }
            }
          }
        }
      }

      //$templateFile = $tempDir . $trimmedMatch;

      //if (in_array($templateFile, $filesToConcat['template']) === false)
      //  $filesToConcat['template'][] = $templateFile;
    }

    // if we have to add a file that we don't have yet...
    if (true === isset($tempFile))
      $parsedFiles[] = $tempFile;
  }
}

/**
 * @param int    $inc            Only for debugging purposes.
 * @param int    $level          Only for debugging purposes.
 * @param string $file
 * @param string $contentToAdd   Actual content to be processed
 * @param array  $parsedFiles    Remaining files to concatenate
 *
 * @return bool False we break, true we continue
 */
function assembleFiles(int &$inc, int &$level, string &$file, string $contentToAdd, array &$parsedFiles)
{
  if (0 === $level)
    showFile($level, $file);

  ++$level;

  // this array will allow us to know which files are remaining to parse
  $filesToConcat = [
    'php' => [
      'use' => [],
      'require' => [],
      'extends' => [],
      'static' => []
    ],
    'template' => []
  ];


  $classesFromFile = getFileNamesFromUses($level, $contentToAdd, $filesToConcat, $parsedFiles);

  // REQUIRE, INCLUDE AND EXTENDS MANAGEMENT
  getFileInfoFromRequiresAndExtends($contentToAdd, $file, $filesToConcat, $parsedFiles, $classesFromFile);

  processStaticCalls($level, $contentToAdd, $filesToConcat, $parsedFiles, $classesFromFile);

  $finalContentParts = [];

  if (false === empty($filesToConcat))
  {
    foreach ($filesToConcat as $fileType => &$entries)
    {
      if ('php' === $fileType)
      {
        foreach($entries as $inclusionMethod => &$phpEntries)
        {
          foreach($phpEntries as $keyOrFile => &$nextFileOrInfo)
          {
            // We increase the process step (DEBUG)
            ++$inc;

            switch($inclusionMethod)
            {
              case 'use' :
                $tempFile = $nextFileOrInfo;
                $method = ' via use statement';
                break;
              case 'require':
                $tempFile = $keyOrFile;
                $method = ' via require/include statement';
                break;
              case 'extends':
                $tempFile = $nextFileOrInfo;
                $method = ' via extends statement';
                break;
              case 'static':
                $tempFile = $nextFileOrInfo;
                $method = ' via static direct call';
            }

            // If it is a class from an external library (not from the framework)
            if (false !== strpos($tempFile, 'vendor'))
            {
              echo CLI_BROWN, 'EXTERNAL LIBRARY : ', $tempFile, END_COLOR, PHP_EOL; // It can be a SwiftMailer class
              // for
              // example
              unset($filesToConcat[$fileType][$inclusionMethod][$tempFile]);
              continue;
            }

            // Files already loaded by default will not be added
            if ($tempFile === BASE_PATH . 'config/Routes.php' || $tempFile === CORE_PATH . 'Router.php')
            {
              echo CLI_BROWN, 'This file will be already loaded by default for each route : ' . substr($tempFile,
                  strlen(BASE_PATH)), END_COLOR, PHP_EOL; // It can be a SwiftMailer class for example
              unset($filesToConcat[$fileType][$inclusionMethod][$tempFile]);
              continue;
            }

            $nextContentToAdd = file_get_contents($tempFile);

            // we remove comments to facilitate the search and replace operations that follow
            $nextContentToAdd = preg_replace('@(//.*)|(/\\*(.|\\s)*?\\*/)@', '', $nextContentToAdd);

            $isReturn = false;

            //if (substr($tempFile, strlen(BASE_PATH)) === 'bundles/config/Routes.php')
            //  echo redText(substr(preg_replace('@\\s@', ' ', $contentToAdd),0, 750)), PHP_EOL;
            //echo redText(substr($tempFile, strlen(BASE_PATH))), PHP_EOL;
            if ('require' === $inclusionMethod)
            {
              //if (strpos($tempFile, 'bundles/config/Routes') !== false)
              //  echo strpos(substr($nextContentToAdd, 3, 9), 'return');

              /* if the file has contents that begin by a return statement then we apply a particular process*/
              if (false !== strpos(substr($nextContentToAdd, 3, 9), 'return'))
              {
                $isReturn = true;
                processReturn($contentToAdd, $nextContentToAdd, $nextFileOrInfo['match'], $nextFileOrInfo['posMatch']);
              }
            }

            showFile($level, $tempFile, $method);
            // If we have a "return type" PHP file then the file have already been included before,
            // in the 'processReturn' function
            if (false === $isReturn)
              $finalContentParts[] = assembleFiles($inc, $level, $tempFile, $nextContentToAdd, $parsedFiles);

            unset($filesToConcat[$fileType][$inclusionMethod][$tempFile]);
          }
        }
      } else // it is a template
      {
        foreach ($entries as $templateEntry)
        {
        //var_dump($templateEntries);die;
          showFile($level, $templateEntry, ' via renderView');
          $nextContentToAdd = file_get_contents($templateEntry);
          ++$inc;
          assembleFiles($inc, $level, $templateEntry, $nextContentToAdd, $parsedFiles);
        }

//        foreach($entries as $nextFileOrInfo => $matchesInfo)
//        {
//          processTemplate($finalContent, $contentToAdd, $match, $posMatch);
//          showFile($level, $nextFileOrInfo, ' TEMPLATE, NOT FULLY IMPLEMENTED FOR NOW !');
//          assembleFiles($inc, $level, $nextFile, $finalContent, $contentToAdd, $parsedFiles);
//        }
      }
    }
  }

  // contentToAdd is "purged" we can add it to the finalContent
  $finalContent = implode(PHP_EOL, $finalContentParts) . $contentToAdd;

  // We increase the process step (DEBUG)
  ++$inc;

  //  ****** // Either we begin the process, either we return to the roots of the process so ... (DEBUG)
  --$level;
  
  return $finalContent;
}

/**
 * We change things like \blabla\blabla\blabla::trial() by blabla::trial() and we include the related files
 *
 * @param int    $level
 * @param string $contentToAdd    Content actually parsed
 * @param array  $filesToConcat   Files to parse after have parsed this one
 * @param array  $parsedFiles     Files already parsed
 * @param array  $classesFromFile Classes that we have retrieved from the previous analysis of use statements
 *                                (useful only for extends statements)
 */
function processStaticCalls(int $level, string &$contentToAdd, array &$filesToConcat, array $parsedFiles, array $classesFromFile)
{
  preg_match_all(
    '@(?:(\\\\{0,1}(?:\\w{1,}\\\\){0,})((\\w{1,}):{2}\\${0,1}\\w{1,}))@',
    $contentToAdd,
    $matches,
    PREG_SET_ORDER | PREG_OFFSET_CAPTURE
  );

  $lengthAdjustment = 0;

  foreach($matches as &$match)
  {
    // if we don't have all the capturing groups, then it is not what we seek
    if (false === isset($match[1][1]))
      continue;

    unset($match[0]);
    $classPath = $match[1][0];
    $offset = $match[1][1];
    $classAndFunction = $match[2][0];
    $class = $match[3][0];

    // match[1][0] is like \xxx\yyy\zzz\
    // match[2][0] is like class::function
    // match[3][0] is like class

    // no need to include self or parent !!
    // renderController is an edge case present in LionelException.php
    // PDO is a native class, no need to import it !
    if (true === in_array($class, ['self', 'parent', 'renderController', 'PDO']))
      continue;

    // str_replace to ensure us that the same character is used each time
    $newFile = BASE_PATH . str_replace('\\', '/', $classPath . $class . '.php');

    // does the class exist ? if not we search the real path of it
    if (false === file_exists($newFile))
    {
      $newFile = searchForClass($classesFromFile, $class, $contentToAdd, $offset);

      // now that we are sure that we have the real path, we test it again
      if (true === in_array($newFile, $parsedFiles, true))
        continue;

      // if we already have included the file
      if (false === $newFile)
        continue;
    }

    // We add the file found in the use statement only if we don't have it yet
    if (false === in_array($newFile, $parsedFiles))
    {
      $parsedFiles[] = $newFile;
      $filesToConcat['php']['static'][] = $newFile;
    } else if (1 < VERBOSE)
    {
      showFile($level, $newFile, ' ALREADY PARSED');
    }

    // We have to readjust the found offset each time with $lengthAdjustment 'cause we change the content length by removing content
    $length = strlen($classPath . $classAndFunction);
    $contentToAdd = substr_replace($contentToAdd,
      $classAndFunction,
      $offset - $lengthAdjustment,
      $length
    );

    // We calculate the new offset for the offset !
    $lengthAdjustment += $length - strlen($classAndFunction);
  }
}

/**
 * Merges files and fixes the usage of namespaces and uses into the concatenated content
 *
 * @param $bundle        string
 * @param $route         string
 * @param $content       string Content to fix
 * @param $verbose       bool
 * @param $fileToInclude mixed  Files to merge
 *
 * @return mixed
 */
//function fixFiles(&$content, &$verbose, &$filesToConcat = [])
function fixFiles(string $bundle, string &$route, string $content, &$verbose, &$fileToInclude = '')
{
  if (defined('VERBOSE') === false)
    define('VERBOSE', (int) $verbose);

  if (0 < VERBOSE)
    define('BASE_PATH_LENGTH', strlen(BASE_PATH));

  // we create these variables only for the reference pass
  $inc = 0;
  $level = 0;
  $parsedFiles = [];
  $contentToAdd = $content;

  if ('' !== $fileToInclude)
    $finalContent = assembleFiles($inc, $level, $fileToInclude, $contentToAdd, $parsedFiles);
  else
  {
    $finalContent = $content;
    preg_match_all('@^\\s{0,}use\\s{1,}[^;]{0,};\\s{0,}@mx', $finalContent, $useMatches, PREG_OFFSET_CAPTURE);
    $offset = 0;

    foreach($useMatches[0] as &$useMatch)
    {
      $length = strlen($useMatch[0]);
      $finalContent = substr_replace($finalContent, '', $useMatch[1] - $offset, $length);
      $offset += $length;
    }
  }

  echo PHP_EOL, str_pad('Files to include ', LOADED_DEBUG_PAD, '.', STR_PAD_RIGHT),
    CLI_GREEN, ' [LOADED]', END_COLOR;

  /** We remove all the declare strict types declarations */
  $finalContent = str_replace('declare(strict_types=1);', '', $finalContent);

  /** We suppress namespaces */
  $finalContent = preg_replace(
    '@\s*namespace [^;]{1,};\s*@',
    '',
    preg_replace('@\?>\s*<\?@', '', $finalContent)
  );

  $finalContent = preg_replace(
    '@(\\\\){0,1}(([\\w]{1,}\\\\){1,})(\\w{1,}[:]{2}\\w{1,}\\()@',
    '$4',
    $finalContent
  );

  // We suppress our markers that helped us for the eval()
  $finalContent = str_replace(['BOOTSTRAP###', '###BOOTSTRAP'], '', $finalContent);

  /* We add the namespace cache\php at the beginning of the file
    then we delete final ... partial ... use statements taking care of not remove use in words as functions or comments like 'becaUSE'
  */

  $vendorNamespaceConfigFile = BASE_PATH . 'bundles/' . $bundle . '/config/vendorNamespaces/' . $route . '.txt';
  $vendorNamespaces = true === file_exists($vendorNamespaceConfigFile) ? file_get_contents($vendorNamespaceConfigFile) : '';
  $patternRemoveUse = '@\buse\b@';

  return '<? declare(strict_types=1);' . PHP_EOL . 'namespace cache\php; ' . $vendorNamespaces . ('<?' == substr($finalContent, 0, 2)
      ? preg_replace($patternRemoveUse, '', substr($finalContent, 2))
      : preg_replace($patternRemoveUse, '', ' ?>' . $finalContent)
    );
}
?>
