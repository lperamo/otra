<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\deployment
 */
declare(strict_types=1);

namespace otra\console\deployment\genBootstrap;

use JetBrains\PhpStorm\ArrayShape;
use otra\OtraException;
use const otra\cache\php\init\CLASSMAP;
// do not delete CORE_VIEWS_PATH and DIR_SEPARATOR without testing as they can be used via eval()
use const otra\cache\php\{BASE_PATH, BUNDLES_PATH, CACHE_PATH, CONSOLE_PATH, CORE_VIEWS_PATH, CORE_PATH, DIR_SEPARATOR};
use const otra\console\{ADD_BOLD, CLI_ERROR, CLI_INDENT_COLOR_FOURTH, CLI_INFO, CLI_SUCCESS, CLI_WARNING, END_COLOR};
use function otra\console\showContextByError;

require CONSOLE_PATH . 'tools.php';

const
  PATTERN = '@\s{0,}
    (?:(?<!//\\s)require(?:_once){0,1}\s[^;]{1,};\s{0,})|
    (?:(?<!//\\s)extends\s[^\{]{1,}\s{0,})|
    (?:->renderView\s{0,}\([^\),]{1,})
    @mx',
  // a previous line in first position (we don't include it for now because the templates' management is not optimal
  // yet)=> (?:(?<!//\\s)self::layout\(\);\s{0,})|

  ANNOTATION_DEBUG_PAD = 80,
  LOADED_DEBUG_PAD = 80,
  PHP_OPEN_TAG_STRING = '<?php',
  PHP_OPEN_TAG_LENGTH = 5,
  PHP_END_TAG_STRING = '?>',
  PHP_END_TAG_LENGTH = 2,
  RETURN_AND_STRICT_TYPE_DECLARATION = 31,

  OTRA_ALREADY_PARSED_LABEL = ' ALREADY PARSED',
  OTRA_KEY_REQUIRE = 'require',
  OTRA_KEY_EXTENDS = 'extends',
  OTRA_KEY_STATIC = 'static',

  OTRA_LABEL_REQUIRE = 'require',
  ADJUST_SPACES_AROUND_REQUIRE_STATEMENT = '@((?<=<\?)(\s){2,})|((\s){2,}(?=\\' . PHP_END_TAG_STRING . '))@',
  LABEL_CONST = 'const ',
  STRLEN_LABEL_CONST = 6,
  NAMESPACE_SEPARATOR = '\\';
define(__NAMESPACE__ . '\\BASE_PATH_LENGTH', strlen(BASE_PATH));

/**
 * We have to manage differently the code that we put into an eval either it is PHP code or not. Example :
 *
 *     <?php declare(strict_types=1);echo \'test\'; ?>
 * becomes
 *     declare(strict_types=1);echo 'test';
 *
 *     <?php declare(strict_types=1);echo 'test';
 * becomes
 *     declare(strict_types=1);echo 'test';<?php
 *
 *     <div></div>
 * becomes
 *     ?><div></div><?php
 *
 * @param string $contentToAdd
 */
function phpOrHTMLIntoEval(string &$contentToAdd) : void
{
  // Beginning of content (+1 to strip the space)
  $contentToAdd = str_starts_with($contentToAdd, PHP_OPEN_TAG_STRING)
    ? mb_substr($contentToAdd, PHP_OPEN_TAG_LENGTH + 1)
    : PHP_END_TAG_STRING . $contentToAdd;

  // Ending of content
  if (PHP_END_TAG_STRING === mb_substr($contentToAdd, - PHP_END_TAG_LENGTH))
    $contentToAdd = mb_substr($contentToAdd, 0, - PHP_END_TAG_LENGTH);
  else
    $contentToAdd .= PHP_OPEN_TAG_STRING;
}

/**
 * @param string $file
 *
 * @return bool
 */
function hasSyntaxErrors(string $file) : bool
{
  // Syntax verification, 2>&1 redirects stderr to stdout
  exec(PHP_BINARY . ' -l ' . $file . ' 2>&1', $output);
  $output = implode(PHP_EOL, $output);

  if (mb_strlen($output) <= 6 || false === mb_strpos($output, 'pars', 7))
    return false;

  echo PHP_EOL, CLI_ERROR, $output, PHP_EOL, PHP_EOL;
  showContextByError($file, $output, 10);

  return true;
}

/**
 * Compress PHP files contents. Beware, it makes some hard compression that can lead to false positives (in strings)
 *
 * @param string $fileToCompress
 * @param string $outputFile
 */
function compressPHPFile(string $fileToCompress, string $outputFile) : void
{
  // php_strip_whitespace doesn't suppress double spaces in string and others. Beware of that rule, the preg_replace
  // is dangerous !
  // strips HTML comments that are not HTML conditional comments
  file_put_contents(
    $outputFile . '.php',
    rtrim(str_replace(
      [
        '; }',
        '} }',
        'strict_types=1); namespace',
        '?> <?php',
        '  ',
        '; ',
        'public function', // a function is public by default...
        'public static function',
        ' = ',
        'if (',
        ' { ',
        ' ?? ',
        ' === ',
        ' == ',
        ' && ',
        ' || '
      ],
      [
        ';}',
        '}}',
        'strict_types=1);namespace',
        '',
        ' ',
        ';',
        'function',
        'static function',
        '=',
        'if(',
        '{',
        '??',
        '===',
        '==',
        '&&',
        '||'
      ],
      preg_replace(
      [
        '@\s{1,}@m',
        '@<!--.*?-->@',
        '@;\s(class\s[^\s]{0,}) { @',
        '@(function\s[^\s]{0,}) {\s{0,}@'

      ],
      [
        ' ',
        '',
        ';$1{',
        '$1{'
      ],
      php_strip_whitespace($fileToCompress)
    ))) . PHP_EOL
  );
  unlink($fileToCompress);
}

/**
 * Puts the final contents into a temporary file.
 * Checks syntax errors.
 * If all was ok, retrieves the contents of the temporary file in the real final file.
 * Then it checks namespaces errors.
 * If all was ok, deletes the temporary file.
 *
 * @param string $content
 * @param string $outputFile
 *
 * @throws OtraException
 */
function contentToFile(string $content, string $outputFile) : void
{
  if (VERBOSE > 0)
    echo PHP_EOL;

  echo PHP_EOL, CLI_INFO, 'FINAL CHECKINGS => ';
  /* Do not suppress the indented lines. They allow testing namespaces problems. We put the file in another directory
     in order to see if namespaces errors are declared at the normal place and not at the temporary place */
  $tempFile = BASE_PATH . 'logs/temporary file.php';
  file_put_contents($tempFile, $content);

  // Test each part of the process in order to precisely detect where there is an error.
  if (GEN_BOOTSTRAP_LINT && hasSyntaxErrors($tempFile))
  {
    echo PHP_EOL, PHP_EOL, CLI_ERROR, '[CLASSIC SYNTAX ERRORS in ' . mb_substr($tempFile, BASE_PATH_LENGTH) . '!]',
      END_COLOR, PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }

  $smallOutputFile = mb_substr($outputFile, BASE_PATH_LENGTH);

  echo CLI_SUCCESS, '[CLASSIC SYNTAX]';

  file_put_contents($outputFile, $content);

  if (GEN_BOOTSTRAP_LINT && hasSyntaxErrors($outputFile))
  {
    echo PHP_EOL, PHP_EOL, CLI_ERROR, '[NAMESPACES ERRORS in ' . $smallOutputFile . '!]', END_COLOR, PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }

  echo CLI_SUCCESS, '[NAMESPACES]', END_COLOR, PHP_EOL;

  if (!unlink($tempFile))
  {
    echo CLI_ERROR, 'There has been an error during removal of the file ', CLI_INFO, $tempFile, CLI_ERROR,
    '. Task aborted.', END_COLOR, PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }
}

/**
 * We analyze the use statement in order to retrieve the name of each class which is included in it.
 *
 * @param int      $level
 * @param array{
 *  php:array{
 *    use ?: string[],
 *    require ?: array<string,array{
 *      match: string,
 *      posMatch: int
 *    }>,
 *    extends ?: string[],
 *    static ?: string[]
 *  },
 *  template: array
 * }               $filesToConcat Files to parse after have parsed this one
 * @param string   $class
 * @param string[] $parsedFiles   Remaining files to concatenate
 * @param string   $chunk         Original chunk (useful for the external library class warning)
 */
function analyzeUseToken(int $level, array &$filesToConcat, string $class, array &$parsedFiles, string $chunk) : void
{
  $class = trim($class);

  // this class is already included in all the cases, no need to have it more than once !
  if ('config\\Router' === $class)
    return;

  // dealing with \ at the beginning of the use
  if (!isset(CLASSMAP[$class]))
  {
    $revisedClass = mb_substr($class, 1);

    if (NAMESPACE_SEPARATOR === $revisedClass)
      $class = $revisedClass;
    elseif (in_array($class, ['DevControllerTrait', 'ProdControllerTrait']))
    {
      if ($class === 'DevControllerTrait')
      {
        echo CLI_INFO, 'We will not send the development controller in production.', END_COLOR, PHP_EOL;
        return;
      }
      // Avoids to consider DevControllerTrait and ProdControllerTrait as external library classes
      $class = 'otra\\' . $class;
    } else
    {
      $cacheNamespace = 'cache\\php';

      // Handles cache/php namespaces and otra namespaces (9 is length of $cacheNamespace)
      if (!str_starts_with($class, $cacheNamespace))
      {
        // It can be a SwiftMailer class for example.
        if (VERBOSE > 0 &&
          !in_array(
            $chunk,
            [
              'ReflectionClass',
              'ReflectionException',
              'ReflectionMethod',
              'ReflectionProperty',
              'SplFileObject',
              'Error',
              'Exception',
              'JetBrains\PhpStorm\Pure',
              'JetBrains\PhpStorm\NoReturn'
            ],
            true
          )
        )
          echo CLI_WARNING, 'EXTERNAL LIBRARY CLASS : ' . $chunk, END_COLOR, PHP_EOL;

        return;
      } elseif ($class === $cacheNamespace . '\\BlocksSystem')
        // The class cache\php\BlocksSystem is already loaded via the MasterController class
        return;
    }
  }

  $tempFile = CLASSMAP[$class];

  // We add the file found in the use statement only if we don't have it yet
  if (!in_array($tempFile, $parsedFiles))
  {
    $filesToConcat['php']['use'][] = $tempFile;
    $parsedFiles[] = $tempFile;
  } elseif (VERBOSE > 1)
    showFile($level, $tempFile, OTRA_ALREADY_PARSED_LABEL);
}

/**
 * We retrieve file names to include that are found via the use statements to $filesToConcat in php/use category.
 * We then clean the use keywords ...
 * This function only evaluates ONE use statement at a time.
 *
 * @param int       $level
 * @param string    $contentToAdd  Content actually parsed
 * @param array{
 *  php:array{
 *    use ?: string[],
 *    require ?: array<string,array{
 *      match: string,
 *      posMatch: int
 *    }>,
 *    extends ?: string[],
 *    static ?: string[]
 *  },
 *  template: array
 * }                $filesToConcat Files to parse after have parsed this one
 * @param string[]  $parsedFiles   Remaining files to concatenate
 * @param string[]  $parsedConstants
 *
 * @return string[] $classesFromFile
 */
function getFileNamesFromUses(
  int $level,
  string &$contentToAdd,
  array &$filesToConcat,
  array &$parsedFiles,
  array &$parsedConstants
) : array
{
  preg_match_all(
    '@^\\s{0,}use\\s{1,}([^;]{0,});\\s{0,}$@mx',
    $contentToAdd,
    $useMatches,
    PREG_OFFSET_CAPTURE
  );

  $classesFromFile = [];

  foreach($useMatches[1] as $useMatch)
  {
    $chunks = explode(',', $useMatch[0]);
    $isConst = str_starts_with($useMatch[0], LABEL_CONST);

    if (str_starts_with($useMatch[0], 'function '))
      continue;

    $beginString = $originalChunk = '';

    foreach ($chunks as &$chunk)
    {
      $chunk = trim($chunk);
      $posLeftParenthesis = mb_strpos($chunk, '{');

      // case use xxx\xxx{XXX, xxx, xxx}; (notice the uppercase, it's where we are, one or more xxx between the braces)
      // example otra\otra\bdd\{Sql, Pdomysql}
      if (false !== $posLeftParenthesis)
      {
        $beginString = mb_substr($chunk, 0, $posLeftParenthesis); // like otra\otra\bdd\

        // originalChunk is used to determine the FQCN that will be displayed if the class comes from an external
        // library class
        if ($originalChunk === '' && $chunk !== '')
          $originalChunk = $beginString;

        $lastChunk = mb_substr($chunk, $posLeftParenthesis + 1); // like Sql

        if ($isConst)
          $constantName = $lastChunk;

        $classToReplace = $beginString . str_replace(' ', '', $lastChunk);

        // simplifies the usage of classes by passing from FQCN to class name ... otra\bdd\Sql => Sql
        $contentToAdd = str_replace($classToReplace, $lastChunk, $contentToAdd);

        // case use xxx\xxx{XXX}; (notice that there is only one name between the braces
        if ($classToReplace[-1] === '}')
          $classToReplace = mb_substr($classToReplace, 0, -1);
      } else
      {
        /** if we have a right parenthesis we strip it and put the content before the beginning of the use statement,
         * otherwise ... if it uses a PHP7 shortcut with parenthesis, we add the beginning of the use statement
         * otherwise ... we just put the string directly */
        if (!str_contains($chunk, '}'))
        {
          if ('' === $beginString) // case use xxx/xxx/xxx;
          {
            $classToReplace = $chunk;
            $tempChunks = explode(NAMESPACE_SEPARATOR, $classToReplace);

            if ($isConst)
              $constantName = $tempChunks[count($tempChunks) - 1];

            // simplifies the usage of classes by passing from FQCN to class name ... otra\bdd\Sql => Sql
            $contentToAdd = str_replace($classToReplace, array_pop($tempChunks), $contentToAdd);
          } else // case use xxx/xxx{xxx, XXX, xxx}; (notice the uppercase, it's where we are)
          {
            $classToReplace = $beginString . $chunk;

            if ($isConst)
              $constantName = $chunk;

            // simplifies the usage of classes by passing from FQCN to class name ... otra\bdd\Sql => Sql
            $contentToAdd = str_replace($classToReplace, $chunk, $contentToAdd);
          }

          $lastChunk = $chunk;

        } else
        { // case use xxx/xxx{xxx, xxx, XXX}; (notice the uppercase, it's where we are)
          $lastChunk = mb_substr($chunk, 0, -1);
          $classToReplace = $beginString . $lastChunk;

          if ($isConst)
            $constantName = $lastChunk;

          // simplifies the usage of classes by passing from FQCN to class name ... otra\bdd\Sql => Sql
          $contentToAdd = str_replace($classToReplace, $lastChunk, $contentToAdd);
        }
      }

      // Example of fully qualified constant name => otra\services\OTRA_KEY_STYLE_SRC_DIRECTIVE
      $fullyQualifiedConstantName = (
        $originalChunk !== ''
          ? (str_ends_with($originalChunk, NAMESPACE_SEPARATOR)
            ? $originalChunk
            : $originalChunk . NAMESPACE_SEPARATOR )
          : ''
        ) . $lastChunk;

      if ($isConst)
        $parsedConstants[$constantName] = substr($fullyQualifiedConstantName, STRLEN_LABEL_CONST);
      else
      {
        // We analyze the use statement in order to retrieve the name of each class which is included in it.
        analyzeUseToken(
          $level,
          $filesToConcat,
          $classToReplace,
          $parsedFiles,
          $fullyQualifiedConstantName
        );

        // The classes will be useful when we will analyze the extends statements
        $classesFromFile[] = $classToReplace;
      }

      // Now that we have retrieved the files to include, we can clean all the use statements
      // We need the modifier m in order to make the ^ work as expected
      $contentToAdd = preg_replace('@^use [^;]{1,};$@m', '', $contentToAdd, -1, $count);
    }
  }

  return $classesFromFile;
}

/**
 * We test if we have dynamic variables into the require/include statement, and replace them if they exists
 *
 * @param string $fileContent  Content of the file $filename
 * @param string $filename
 * @param string $trimmedMatch Match (with spaces neither to the left nor to the right) in the file $filename that
 *                             potentially contains dynamic variable to change
 *
 * @throws OtraException
 * @return array{0: string, 1: bool} [$fileContent, $isTemplate]
 */
#[ArrayShape([
  'string',
  'bool'
])]
function evalPathVariables(string &$fileContent, string $filename, string $trimmedMatch) : array
{
  // no path variables found
  if (!str_contains($trimmedMatch, '$'))
    return [$fileContent, false];

  preg_match_all('@\\$([^\\s)(]+)@', $fileContent, $pathVariables);
  $isTemplate = false;

  if (!empty($pathVariables))
  {
    // we don't need the complete mask
    unset($pathVariables[0]);

    foreach($pathVariables as $pathVariable)
    {
      if (isset(PATH_CONSTANTS[$pathVariable[0]]))
        $fileContent = str_replace(
          '$' . $pathVariable[0],
          '\'' . PATH_CONSTANTS[$pathVariable[0]] . '\'',
          $fileContent
        );
      /* we make an exception for this particular require statement because
         it is a `require` made by the prod controller, and then it is a template ...(so no need to include it, for now)
       */
      elseif ('templateFilename' === trim($pathVariable[0]))
        $isTemplate = true;
      elseif ('require_once CACHE_PATH . \'php/\' . $route . \'.php\';' !== $trimmedMatch
        && 'require $routeSecurityFilePath;' !== $trimmedMatch
        && 'require $renderController->viewPath . \'renderedWithoutController.phtml\';' !== $trimmedMatch
      )
      {
        echo CLI_ERROR, 'CANNOT EVALUATE THE REQUIRE STATEMENT BECAUSE OF THE NON DEFINED DYNAMIC VARIABLE ', CLI_WARNING,
        '$', $pathVariable[0], CLI_ERROR, ' in ', CLI_WARNING, $trimmedMatch, CLI_ERROR, ' in the file ', CLI_WARNING,
        $filename, CLI_ERROR, ' !', END_COLOR, PHP_EOL;
        throw new OtraException(code: 1, exit: true);
      }

      // if the last condition was true => we must not change this line from CORE_PATH . Router.php so we pass to the
      // next loop iteration!
    }
  }

  return [$fileContent, $isTemplate];
}

/**
 * Shows the file name in the console for debug purposes
 *
 * @param int    $level
 * @param string $fileAbsolutePath
 * @param string $otherText
 */
function showFile(int $level, string $fileAbsolutePath, string $otherText = ' first file') : void
{
  if (VERBOSE > 0)
    echo str_pad(
      str_repeat(' ', $level << 1) . (0 !== $level ? '| ' : '') .
      mb_substr($fileAbsolutePath, BASE_PATH_LENGTH),
      ANNOTATION_DEBUG_PAD,
      '.'
    ), CLI_WARNING, $otherText, END_COLOR, PHP_EOL;
}

/**
 * We escape the single quotes only in the PHP portions of the file that we want to add
 * (This content needs to begin by <?php and finish by ?>)
 *
 * @param string $contentToAdd
 */
function escapeQuotesInPhpParts(string &$contentToAdd) : void
{
  $offset = 0;

  while (false !== ($posBeginPHP = strpos($contentToAdd, PHP_OPEN_TAG_STRING, $offset)))
  {
    $length = strpos($contentToAdd, PHP_END_TAG_STRING, $posBeginPHP) - $posBeginPHP;

    $contentToAdd = substr_replace(
      $contentToAdd,
      str_replace(
        '\'',
        '\\\'',
        substr($contentToAdd, $posBeginPHP, $length),
        $nbReplacements),
      $posBeginPHP,
      $length
    );

    $offset = $posBeginPHP + PHP_OPEN_TAG_LENGTH + $nbReplacements;
  }
}

/**
 * Extracts the file name and the 'isTemplate' information from the require/include statement.
 * Potentially replaces dynamic $variables by their value to know which file to use.
 *
 * @param string $trimmedMatch Match (with spaces neither to the left nor to the right) in the file $filename that
 *                             potentially contains dynamic variable to change
 * @param string $filename
 *
 * @throws OtraException
 * @return array{0: string, 1: bool} [$tempFile, $isTemplate]
 */
#[ArrayShape([
  'string',
  'bool'
])]
function getFileInfoFromRequireMatch(string $trimmedMatch, string $filename) : array
{
  // Extracts the file name in the require/include statement ...
  preg_match('@(?:require|include)(?:_once)?\s*([^;]+)\\)?@m', $trimmedMatch, $inclusionMatches);
  $fileContent = $inclusionMatches[1];

  /* Not sure of the safety of this code
     We check if the require/include statement is in a function call
     by counting the number of parenthesis between the require statement and the semicolon */
  if (0 !== (mb_substr_count($trimmedMatch, ')') + mb_substr_count($trimmedMatch, '(')) % 2 )
    $fileContent = mb_substr($fileContent, 0, -1);

  return evalPathVariables($fileContent, $filename, $trimmedMatch);
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
function processTemplate(string &$finalContent, string &$contentToAdd, string $match, int $posMatch) : string
{
  escapeQuotesInPhpParts($contentToAdd);
  $pregQuotedMatch = preg_quote($match);

  /* We ensure us that the content have only one space after the PHP opening tag and before the PHP ending tag
   * that surrounds the require statement */
  $finalContent = preg_replace(ADJUST_SPACES_AROUND_REQUIRE_STATEMENT, ' ', $finalContent);

  // We have to process the $match value because we have modified the code
  $newMatch = preg_replace('@\s{2,}@', '', $match);

  // Checks if our $match is in an eval()
  $inEval = preg_match('@(?<=BOOTSTRAP###)[\S\s]*' . $pregQuotedMatch . '[\S\s]*(?=###BOOTSTRAP)@', $finalContent);

  /* If our $match is in PHP code then we determine the replacement string according to the context */
  if (1 === preg_match('@(?<=<\?).*' . $pregQuotedMatch . '.*(?=\\' . PHP_END_TAG_STRING . ')@', $finalContent))
  {
    // We remove PHP tags
    $finalContent = substr_replace($finalContent, $newMatch, $posMatch - PHP_OPEN_TAG_LENGTH, $posMatch);

    $replacement = $contentToAdd;
  } else
  {
    phpOrHTMLIntoEval($contentToAdd);
    $replacement = 'eval(' . (1 === $inEval
      ? '\\\'BOOTSTRAP###' . $contentToAdd . '###BOOTSTRAP\\'
      : '\'BOOTSTRAP###' . $contentToAdd . '###BOOTSTRAP')
      . '\'); ';
  }

  // Adds the code in the finalContent
  return substr_replace(
    $finalContent,
    $replacement,
    mb_strpos($finalContent, $newMatch),
    mb_strlen($newMatch)
  );
}

/**
 * Process the return inside the PHP content from $contentToAdd and include it directly in the $finalContent.
 *
 * @param string $includingCode    The whole file that contains the $inclusionCode
 * @param string $includedCode     Like "<?php declare(strict_types=1);return [...];?>"
 * @param string $inclusionCode    Like "'require BUNDLES_PATH . 'config/Routes.php');"
 * @param int    $inclusionCodePos $inclusionCode position in $includingCode
 */
function processReturn(string &$includingCode, string &$includedCode, string $inclusionCode, int $inclusionCodePos) : void
{
  // -5 for the semicolon, the ending tag and the mandatory line break
  // That way, we then only retrieve the needed array
  $includedCode = trim(mb_substr(
    $includedCode,
    PHP_OPEN_TAG_LENGTH + RETURN_AND_STRICT_TYPE_DECLARATION,
    -(2 + PHP_END_TAG_LENGTH)
  ));

  // We change only the `require` but we keep the parenthesis and the semicolon
  // (cf. BASE_PATH . config/Routes.php init function)
  preg_match(
    '@^\s*(?>require|include)(?>_once)?\s(?>(?=\\N)([^)]))+(\s*\);)@',
    $inclusionCode,
    $matches,
    PREG_OFFSET_CAPTURE
  );

  // We remove the `require`s like statements before adding the content
  $includingCode = substr_replace($includingCode, $includedCode, $inclusionCodePos, $matches[2][1]);
}

/**
 * Returns false if :
 *
 * - We found this class in already parsed classes
 * - There is no namespace
 * - The class is not part of the generated classmap
 *
 * @param array  $classesFromFile Already parsed classes
 * @param string $class           Class to analyze/search
 * @param string $contentToAdd
 * @param int    $match           Content extract position where the class was found
 *
 * @return false|string $tempFile
 */
function searchForClass(array $classesFromFile, string $class, string $contentToAdd, int $match) : false|string
{
  // Do we already have this class ?
  /** @var string $classFromFile */
  foreach($classesFromFile as $classFromFile)
  {
    if (false !== mb_strrpos($classFromFile, $class, (int) mb_strrpos($classFromFile,',')))
      return false;
  }
  // if it's not the case ... we search it in the directory of the file that we are parsing
  // /!\ BEWARE ! Maybe we don't have handled the case where the word namespace is in a comment.
  // we use a namespace so ...

  // we search the namespace in the content before the extends call
  $namespacePosition = mb_strrpos($contentToAdd, 'namespace', $match - mb_strlen($contentToAdd));

  // no namespace found, we return false
  if ($namespacePosition === false)
    return false;

  // then we find the section that interests us
  $tempContent = mb_substr($contentToAdd, $namespacePosition, $match - $namespacePosition);

  // then we can easily extract the namespace (10 = strlen('namespace'))
  // and concatenates it with a '\' and the class to get our file name
  $newClass = mb_substr(
    $tempContent,
    10,
    mb_strpos($tempContent, ';') - 10
  ) . NAMESPACE_SEPARATOR . $class;

  if (!isset(CLASSMAP[$newClass]))
  {
    if (VERBOSE > 0)
      echo CLI_WARNING, 'Notice : Please check if you use a class ', CLI_INFO, $class, CLI_WARNING,
        ' in a use statement but this file seems to be not included ! Maybe the file name is only in a comment though.',
        END_COLOR, PHP_EOL;

    return false;
  }

  return CLASSMAP[$newClass];
}

/**
 * Retrieves information about what kind of file inclusion we have, the related code and its position.
 *
 * @param array{
 *   level: int,
 *   contentToAdd: string,
 *   filename: string,
 *   filesToConcat: array{
 *     php:array{
 *       use ?: string[],
 *       require ?: array<string,array{
 *         match: string,
 *         posMatch: int
 *       }>,
 *       extends ?: string[],
 *       static ?: string[]
 *     },
 *     template: array
 *   },
 *  parsedFiles: string[],
 *  classesFromFile: array,
 *  parsedConstants: array<string,string>
 * } $parameters
 * $level           Only for debugging purposes.
 * $contentToAdd    Content actually parsed
 * $filename        Name of the file actually parsed
 * $filesToConcat   Files to parse after have parsed this one
 * $parsedFiles     Remaining files to concatenate
 * $classesFromFile Classes that we have retrieved from the previous analysis of use statements
 *                  (useful only for extends statements)
 * $parsedConstants Constants detected in the parsed content
 *
 * @throws OtraException
 */
function getFileInfoFromRequiresAndExtends(array &$parameters) : void
{
//  list($level, $contentToAdd, $filename, $filesToConcat, &$parsedFiles, $classesFromFile, &$parsedConstants) = $parameters;
  [
    'level' => $level,
    'contentToAdd' => $contentToAdd,
    'filename' => $filename,
    'filesToConcat' => &$filesToConcat,
    'parsedFiles' => &$parsedFiles,
    'classesFromFile' => $classesFromFile,
    'parsedConstants' => $parsedConstants
  ] = $parameters;

  preg_match_all(PATTERN, $contentToAdd, $matches, PREG_OFFSET_CAPTURE);

  // For all the inclusions
  foreach($matches[0] as $match)
  {
    if ('' === $match[0])
      continue;

    $trimmedMatch = trim(preg_replace('@\s{1,}@', ' ', $match[0]));
    /** WE RETRIEVE THE CONTENT TO PROCESS, NO TRANSFORMATIONS HERE */

    /** REQUIRE OR INCLUDE STATEMENT EVALUATION */
    if (str_contains($trimmedMatch, OTRA_LABEL_REQUIRE))
    {
      [$tempFile, $isTemplate] = getFileInfoFromRequireMatch($trimmedMatch, $filename);

      /* we make an exception for this particular require statement because
         it is a `require` made by the prod controller, and then it is a template ...
         (so no need to include it because html templates management is not totally functional right now) */
      if (!$isTemplate) // if the file to include is not a template
      {
        // If we find __DIR__ in the include/require statement then we replace it with the good folder and not the actual folder (...console ^^)
        $posDir = strpos($tempFile, '__DIR__ .');

        if ($posDir !== false)
        {
          $tempFile = substr_replace(
            '__DIR__ . ',
            '\'' . dirname($filename) . '/' . basename(mb_substr($tempFile, $posDir, -1)) . '\'',
            $posDir,
            9 // 9 is the length of the string '__DIR__ .'
          );
        }

        // we must not change these inclusions from
        // - CORE_PATH . Router.php
        // - securities configuration
        // - dump tool
        if (in_array(
          $tempFile,
          [
            'CACHE_PATH . \'php/\' . $route . \'.php\'',
            '$routeSecurityFilePath',
            'CORE_PATH . \'tools/debug/\' . OTRA_DUMP_FINAL_CLASS . \'.php\'',
            '$renderController->viewPath . \'renderedWithoutController.phtml\''
          ],
          true)
        )
          continue;

        if ($tempFile === 'CORE_PATH . \'prod\' . DIR_SEPARATOR . ucfirst(\'prod\') . \'ControllerTrait.php')
          $tempFile .= "'";

        /** @var string $tempFile */
        // Here we handle the namespaces issues (if we were not using namespaces, we would not need this condition
        if (preg_match_all(
          '@BASE_PATH|BUNDLES_PATH|CORE_PATH|DIR_SEPARATOR@',
          $tempFile,
          $constantsMatches
        ))
        {
          foreach ($constantsMatches[0] as $constantsMatch)
          {
            $tempFile = str_replace($constantsMatch, 'otra\\cache\\php\\' . $constantsMatch, $tempFile);
          }
        }

        if (!empty($parsedConstants))
        {
          // str_replace to ensure us that the same character '/' is used each time
          $constantPattern = '';

          foreach ($parsedConstants as $constantString => $fullyQualifiedConstantName)
          {
            $constantPattern .= '(?<!\\\\)' . $constantString . '|';
          }

          $constantPattern = substr($constantPattern, 0, -1);
          preg_match_all('@' . $constantPattern . '@', $tempFile, $constantMatches);

          foreach ($constantMatches as $constantMatch)
          {
            if (!empty($constantMatch))
              $tempFile = str_replace($constantMatch, $parsedConstants[$constantMatch[0]], $tempFile);
          }
        }

        $tempFile = str_replace(NAMESPACE_SEPARATOR, '/', eval('return ' . $tempFile . ';'));

        // we must not take care of the bundles/config/Config.php as it is an optional config file.
        if ($tempFile === BUNDLES_PATH . 'config/Config.php')
          continue;

        if (VERBOSE > 0 && !str_contains($tempFile, BASE_PATH))
          echo PHP_EOL, CLI_WARNING, 'BEWARE, you have to use absolute path for files inclusion ! \'' . $tempFile,
          '\' in ', $filename, '.', PHP_EOL,
          'Ignore this warning if your path is already an absolute one or your file is outside of the project folder.',
          END_COLOR, PHP_EOL;

        if (!file_exists($tempFile))
        {
          echo PHP_EOL, CLI_ERROR, 'There is a problem with ', CLI_WARNING, $trimmedMatch, CLI_ERROR, ' => ',
            CLI_WARNING, $tempFile, CLI_ERROR, ' in ', CLI_WARNING, $filename, CLI_ERROR, ' !', END_COLOR, PHP_EOL,
            PHP_EOL;
          throw new OtraException(code: 1, exit: true);
        }

        if (in_array($tempFile, $parsedFiles, true))
          continue;

        // actually it works only with 'str_pos' not 'mb_strpos'!
        $filesToConcat['php'][OTRA_KEY_REQUIRE][$tempFile] = [
          'match' => $match[0],
          'posMatch' => strpos($contentToAdd, $match[0])
        ];
      }
    } elseif(str_contains($trimmedMatch, OTRA_KEY_EXTENDS))
    { // Extends block is only tested if the class has not been loaded via a use statement before

      // Extracts the file name in the extends statement ... (8 = strlen('extends '))
      $class = mb_substr($trimmedMatch, 8);

      // if the class begin by \ then it is a standard class, and then we do nothing
      if (NAMESPACE_SEPARATOR === $class[0])
        continue;

      $tempFile = searchForClass($classesFromFile, $class, $contentToAdd, $match[1]);

      // If we already have included the class
      if (false === $tempFile)
        continue;

      if (in_array($tempFile, $parsedFiles))
      {
        if (VERBOSE > 1)
          showFile($level, $tempFile, OTRA_ALREADY_PARSED_LABEL);

        continue;
      }

      $filesToConcat['php'][OTRA_KEY_EXTENDS][] = $tempFile;
    }// elseif(!str_contains($filename, 'prod/Controller.php'))
    //{ /** TEMPLATE via framework 'renderView' (and not containing method signature)*/
//      $trimmedMatch = mb_substr($trimmedMatch, mb_strpos($trimmedMatch, '(') + 1);
//
//      // If the template file parameter supplied for renderView method is just a string
//      if ($trimmedMatch[0] === '\'')
//      {
//        $trimmedMatch = mb_substr($trimmedMatch, 1, -1);
//      } else // More complicated...
//      {
//      }

//      $tempDir = '';

//      if (!file_exists($trimmedMatch))
//      {
//        $tempDir = str_replace(NAMESPACE_SEPARATOR, '/', dirname($filename));
//
//        if (!file_exists($tempDir . $trimmedMatch))
//        {
//          // Retrieves the last directory name which is (maybe) the specific controller directory name which we will use as a view directory name instead
//          $tempDir = realpath($tempDir . '/../../views' . mb_substr($tempDir, mb_strrpos($tempDir, '/'))) . '/';
//
//          if (!file_exists($tempDir . $trimmedMatch))
//          {
//            if ($trimmedMatch === '/exception.phtml')
//              $tempDir = CORE_PATH . 'views/' ;
//            // no ? so where is that file ?
//            elseif (!str_contains($trimmedMatch, 'html'))
//            {
//              echo CLI_ERROR, '/!\\ We cannot find the file ', CLI_WARNING, $trimmedMatch, CLI_ERROR, ' seen in ' .
//                CLI_WARNING,
//              $filename,
//              CLI_ERROR, '. ', PHP_EOL, 'Please fix this and try again.', PHP_EOL, END_COLOR;
//              throw new OtraException(code: 1, exit: true);
//            }
//          }
//        }
//      }

      //$templateFile = $tempDir . $trimmedMatch;

      //if (in_array($templateFile, $filesToConcat['template']) === false)
      //  $filesToConcat['template'][] = $templateFile;
//    }

    /** @var ?string $tempFile */
    // if we have to add a file that we don't have yet...
    if (isset($tempFile))
      $parsedFiles[] = $tempFile;
  }
}

/**
 * @param int      $increment       Only for debugging purposes.
 * @param int      $level           Only for debugging purposes.
 * @param string   $filename
 * @param string   $contentToAdd    Actual content to be processed
 * @param string[] $parsedFiles     Remaining files to concatenate
 * @param string[] $parsedConstants Constants parsed from 'use' statements
 *
 * @throws OtraException
 * @return string $finalContent
 */
function assembleFiles(
  int &$increment,
  int &$level,
  string $filename,
  string $contentToAdd,
  array &$parsedFiles,
  array &$parsedConstants
) : string
{
  if (0 === $level && VERBOSE > 1)
    showFile($level, $filename);

  ++$level;

  // this array will allow us to know which files are remaining to parse
  $filesToConcat = [
    'php' => [
      'use' => [],
      OTRA_KEY_REQUIRE => [],
      OTRA_KEY_EXTENDS => [],
      OTRA_KEY_STATIC => []
    ],
    'template' => []
  ];
  $classesFromFile = getFileNamesFromUses(
    $level,
    $contentToAdd,
    $filesToConcat,
    $parsedFiles,
    $parsedConstants
  );

  $toPassAsReference = [
    'level' => $level,
    'contentToAdd' => $contentToAdd,
    'filename' => $filename,
    'filesToConcat' => $filesToConcat,
    'parsedFiles' => $parsedFiles,
    'classesFromFile' => $classesFromFile,
    'parsedConstants' => $parsedConstants
  ];
  // REQUIRE, INCLUDE AND EXTENDS MANAGEMENT
  getFileInfoFromRequiresAndExtends(
    $toPassAsReference
  );

  [
    'level' => $level,
    'contentToAdd' => $contentToAdd,
    'filename' => $filename,
    'filesToConcat' => $filesToConcat,
    'parsedFiles' => $parsedFiles,
    'classesFromFile' => $classesFromFile,
    'parsedConstants' => $parsedConstants
  ] = $toPassAsReference;

  processStaticCalls($level, $contentToAdd, $filesToConcat, $parsedFiles, $classesFromFile);
  $finalContentParts = [];

  if (!empty($filesToConcat))
  {
    /**
     * @var string $fileType
     * @var array{
     *   use ?: string[],
     *   require ?: array<string,array{
     *    match: string,
     *    posMatch: int
     *   }>,
     *   extends ?: string[],
     *   static ?: string[]
     * } | string[] $entries
     */
    foreach ($filesToConcat as $fileType => $entries)
    {
      if ('php' === $fileType)
      {
        /**
         * @var string $inclusionMethod
         * @var string[]|array<string,array{match:string,posMatch:int}> $phpEntries
         */
        foreach($entries as $inclusionMethod => $phpEntries)
        {
          // $nextFileOrInfo is an array only if we are in a require/include statement
          foreach($phpEntries as $keyOrFile => $nextFileOrInfo)
          {
            // We increase the process step (DEBUG)
            ++$increment;

            /**
             *  @var string $tempFile
             *  @var string $method
             */
            switch($inclusionMethod)
            {
              case 'use' :
                $tempFile = $nextFileOrInfo;
                $method = ' via use statement';
                break;
              case OTRA_KEY_REQUIRE:
                $tempFile = $keyOrFile;
                $method = ' via require/include statement';
                break;
              case OTRA_KEY_EXTENDS:
                $tempFile = $nextFileOrInfo;
                $method = ' via extends statement';
                break;
              case OTRA_KEY_STATIC:
                $tempFile = $nextFileOrInfo;
                $method = ' via static direct call';
            }

            /** @var string $tempFile In all cases, this is a string,
             *                        redundant PHPDoc but Psalm does not understand otherwise
             */
            // If it is a class from an external library (not from the framework),
            // we let the inclusion code, and we do not add the content to the bootstrap file.
            if (str_contains($tempFile, 'vendor') && !str_contains($tempFile, 'otra'))
            {
              // It can be a SwiftMailer class for example
              echo CLI_WARNING, 'EXTERNAL LIBRARY : ', $tempFile, END_COLOR, PHP_EOL;
              unset($filesToConcat[$fileType][$inclusionMethod][$tempFile]);
              continue;
            }

            // Files already loaded by default will not be added
            if ($filename !== CORE_PATH . 'Router.php'
              && ($tempFile === BASE_PATH . 'config/Routes.php' || $tempFile === CORE_PATH . 'Router.php'))
            {
              echo CLI_WARNING, 'This file will be already loaded by default for each route : ' .
                substr($tempFile, BASE_PATH_LENGTH), END_COLOR, PHP_EOL;
              unset($filesToConcat[$fileType][$inclusionMethod][$tempFile]);
              continue;
            }

            // The file CORE_PATH . templating/blocks.php needs an alias for CORE_PATH . MasterController.php so we have
            // to handle that case
            if ($tempFile === CACHE_PATH . 'php/MasterController.php')
              $tempFile = CORE_PATH . 'MasterController.php';

            $nextContentToAdd = file_get_contents($tempFile) . PHP_END_TAG_STRING;

            // we remove comments like // and /* bla */ to facilitate the search and replace operations that follow
            //$nextContentToAdd = preg_replace('@(//.*)|(/\*(.|\s)*?\*/)@', '', $nextContentToAdd);

            $isReturn = false;

            if (OTRA_KEY_REQUIRE === $inclusionMethod
              /* if the file has contents that begin by a return statement and strict type declaration then we apply a
               particular process*/
              && str_contains(
                mb_substr(
                  $nextContentToAdd,
                  PHP_OPEN_TAG_LENGTH + 1,
                  PHP_OPEN_TAG_LENGTH + RETURN_AND_STRICT_TYPE_DECLARATION
                ),
                'return'
              ))
            {
              /** @var array{match:string,posMatch:int} $nextFileOrInfo $isReturn */
              $isReturn = true;
              processReturn(
                $contentToAdd,
                $nextContentToAdd,
                $nextFileOrInfo['match'],
                $nextFileOrInfo['posMatch']
              );
            }

            if (str_contains($filename, 'config/AllConfig') && !in_array($filename, $parsedFiles, true))
              $finalContentParts[]= $contentToAdd;

            if (VERBOSE > 1)
              showFile($level, $tempFile, $method);

            // If we have a "return type" PHP file then the file have already been included before,
            // in the 'processReturn' function
            if (!$isReturn)
            {
              $finalContentParts[] =
                assembleFiles(
                  $increment,
                  $level,
                  $tempFile,
                  $nextContentToAdd,
                  $parsedFiles,
                  $parsedConstants
                );
            }

            unset($filesToConcat[$fileType][$inclusionMethod][$tempFile]);
          }
        }
      } else // it is a template
      {
        /** @var string $templateEntry */
        foreach ($entries as $templateEntry)
        {
          if (VERBOSE > 1)
            showFile($level, $templateEntry, ' via renderView');

          $nextContentToAdd = file_get_contents($templateEntry) . PHP_END_TAG_STRING;
          ++$increment;
          assembleFiles(
            $increment,
            $level,
            $templateEntry,
            $nextContentToAdd,
            $parsedFiles,
            $parsedConstants
          );
        }

//        foreach($entries as $nextFileOrInfo => $matchesInfo)
//        {
//          processTemplate($finalContent, $contentToAdd, $match, $posMatch);
//          showFile($level, $nextFileOrInfo, ' TEMPLATE, NOT FULLY IMPLEMENTED FOR NOW !');
//          assembleFiles($increment, $level, $nextFile, $finalContent, $contentToAdd, $parsedFiles, $parsedConstants);
//        }
      }
    }
  }

  // contentToAdd is "purged" we can add it to the finalContent
  $finalContent = implode(PHP_EOL, $finalContentParts) . $contentToAdd;

  // We increase the process step (DEBUG)
  ++$increment;

  //  ****** // Either we begin the process, either we return to the roots of the process so ... (DEBUG)
  --$level;

  return $finalContent;
}

/**
 * We change things like \blabla\blabla\blabla::trial() by blabla::trial() and we include the related files
 *
 * @param int       $level
 * @param string    $contentToAdd    Content currently parsed
 * @param array{
 *  php:array{
 *    use ?: string[],
 *    require ?: array<string,array{
 *      match: string,
 *      posMatch: int
 *    }>,
 *    extends ?: string[],
 *    static ?: string[]
 *  },
 *  template: array
 * }                $filesToConcat   Files to parse after have parsed this one
 * @param string[]  $parsedFiles     Files already parsed
 * @param array     $classesFromFile Classes that we have retrieved from the previous analysis of use statements
 *                                (useful only for extends statements)
 */
function processStaticCalls(
  int $level,
  string &$contentToAdd,
  array &$filesToConcat,
  array &$parsedFiles,
  array $classesFromFile
) : void
{
  preg_match_all(
    '@(\\\\{0,1}(?:\\w{1,}\\\\){0,})((\\w{1,}):{2}\\${0,1}\\w{1,})@',
    $contentToAdd,
    $matches,
    PREG_SET_ORDER | PREG_OFFSET_CAPTURE
  );

  $lengthAdjustment = 0;

  foreach($matches as &$match)
  {
    // if we don't have all the capturing groups, then it is not what we seek
    if (!isset($match[1][1]))
      continue;

    unset($match[0]);
    [$classPath, $offset] = $match[1];
    $classAndFunction = $match[2][0];
    $class = $match[3][0];

    // match[1][0] is like \xxx\yyy\zzz\
    // match[2][0] is like class::function
    // match[3][0] is like class

    // no need to include self or parent !!
    // renderController is an edge case present in OtraException.php
    // PDO is a native class, no need to import it !
    if (in_array($class, ['self', 'parent', 'renderController', 'PDO']))
      continue;

    // str_replace to ensure us that the same character is used each time
    $newFile = BASE_PATH . str_replace(NAMESPACE_SEPARATOR, '/', $classPath . $class . '.php');

    // does the class exist ? if not we search the real path of it
    if (!file_exists($newFile))
    {
      $newFile = searchForClass($classesFromFile, $class, $contentToAdd, $offset);

      // now that we are sure that we have the real path, we test it again
      // and if we already have included the file, we also continue
      if (in_array($newFile, $parsedFiles, true) || false === $newFile)
        continue;
    }

    // We add the file found in the use statement only if we don't have it yet
    if (!in_array($newFile, $parsedFiles))
    {
      $parsedFiles[] = $newFile;
      $filesToConcat['php'][OTRA_KEY_STATIC][] = $newFile;
    } elseif (VERBOSE > 1)
      showFile($level, $newFile, OTRA_ALREADY_PARSED_LABEL);

    // We have to readjust the found offset each time with $lengthAdjustment 'cause we change the content length by removing content
    $length = mb_strlen($classPath . $classAndFunction);
    $contentToAdd = substr_replace($contentToAdd,
      $classAndFunction,
      $offset - $lengthAdjustment,
      $length
    );

    // We calculate the new offset for the offset !
    $lengthAdjustment += $length - mb_strlen($classAndFunction);
  }
}

/**
 * Merges files and fixes the usage of namespaces and uses into the concatenated content
 *
 * @param string  $bundle
 * @param string  $route
 * @param string  $content       Content to fix
 * @param int     $verbose
 * @param string  $fileToInclude File to merge
 *
 * @throws OtraException
 * @return string
 */
function fixFiles(string $bundle, string $route, string $content, int $verbose, string $fileToInclude = '') : string
{
  if (!defined(__NAMESPACE__ . '\\VERBOSE'))
    define(__NAMESPACE__ . '\\VERBOSE', $verbose);

  // we create these variables only for the reference pass
  $increment = 0; // process steps counter (more granular than $level variable)
  $level = 0; //  depth level of require/include calls

  // For the moment, as a workaround, we had temporary explicitly added the OtraException file to solve issues.
  $parsedFiles = [CORE_PATH . 'OtraException.php'];
  $contentToAdd = $content;
  $parsedConstants = [];

  if ('' !== $fileToInclude)
    $finalContent = assembleFiles(
      $increment,
      $level,
      $fileToInclude,
      $contentToAdd,
      $parsedFiles,
      $parsedConstants
    );
  else
  {
    $finalContent = $content;
    preg_match_all(
      '@^\\s{0,}use\\s{1,}[^;]{0,};\\s{0,}$@mx',
      $finalContent,
      $useMatches,
      PREG_OFFSET_CAPTURE
    );
    $offset = 0;

    foreach($useMatches[0] as $useMatch)
    {
      $length = mb_strlen($useMatch[0]);
      $finalContent = substr_replace($finalContent, '', $useMatch[1] - $offset, $length);
      $offset += $length;
    }
  }

  if (VERBOSE > 0)
    echo PHP_EOL;

  echo str_pad('Files to include ', LOADED_DEBUG_PAD, '.'), CLI_SUCCESS, ' [LOADED]', END_COLOR;

  /** We remove all the `declare(strict_types=1);` declarations */
  $finalContent = str_replace(
    [
      'declare(strict_types=1);',
      // We suppress our markers that helped us for the eval()
      'BOOTSTRAP###',
      '###BOOTSTRAP'
    ],
    [''],
    $finalContent
  );

  $finalContent = preg_replace(
    [
      '@\\' . PHP_END_TAG_STRING . '\s*<\?php@', // We stick the php files by removing end and open php tag between files
      '@^\s*namespace [^;]{1,};\s*$@m', // We suppress namespaces
      '@\\\\{0,1}(PDO(?:Statement){0,1})@' // We fix PDO and PDOStatement namespaces
    ],
    [
      '',
      '',
      '\\\\$1'
    ],
    $finalContent
  );

  $vendorNamespaceConfigFile = BUNDLES_PATH . $bundle . '/config/vendorNamespaces/' . $route . '.txt';
  $vendorNamespaces = file_exists($vendorNamespaceConfigFile)
    ? file_get_contents($vendorNamespaceConfigFile) . PHP_END_TAG_STRING
    : '';

  /**
   * START SECTION
   */
  $finalContent = str_replace(
    [
      // line from src/Controller.php
      'require CORE_PATH . (\'cli\' === PHP_SAPI ? \'prod\' : $_SERVER[\'APP_ENV\']) . \'/Controller.php\';',
      // line at the top of src/OtraException.php
      'require_once CORE_PATH . \'tools/debug/dump.php\';',
      // line 115 in getDB, Sql class => src/bdd/Sql.php
      'require CORE_PATH . \'bdd/\' . $driver . \'.php\';',
      // line in renderView, file src/prod/Controller.php:57
      'require CORE_PATH . \'Logger.php\';',
      // line in OtraException at the beginning of the method errorMessage()
      'require_once BASE_PATH . \'config/AllConfig.php\';',
      // line in generic AllConfig file
      'require_once BASE_PATH . \'config/\' . $_SERVER[APP_ENV] . \'/AllConfig.php\';',
      // line in MasterController
      'require CORE_PATH . \'services/securityService.php\';',
      // PHPStorm PHP attributes
      '#[Pure] ',
      '#[NoReturn] '
    ],
    '',
    $finalContent
  );
  /** END SECTION */

  // Finalizing the constants imports
  $parsedConstantString = 'use const ';

  $parsedConstants = array_keys($parsedConstants);

  foreach ($parsedConstants as $parsedConstant)
  {
    $parsedConstantString .= $parsedConstant . ',';
  }

  // after that line $parsedConstantString contains something like
  // use const APP_ENV,BASE_PATH,CACHE_PATH,DIR_SEPARATOR,PROD,BUNDLES_PATH;
  $parsedConstantString = substr($parsedConstantString, 0, -1) . ';';

  // If we have PHP we strip the beginning PHP tag to include it after the PHP code,
  // otherwise we add an ending PHP tag to begin the HTML code.
  $patternRemoveUse = '@^\buse\b@m';

  // We add the namespace otra\cache\php at the beginning of the file AND we add the security services functions
  // then we delete final ... partial ... use statements taking care of not remove use in words as functions or comments
  // like 'becaUSE'
  return PHP_OPEN_TAG_STRING . ' declare(strict_types=1); ' . PHP_EOL .
    'namespace otra\\cache\\php;' .
    ($fileToInclude !== '/var/www/html/perso/otra/src/Router.php'
      ? 'use \\Exception; use \\stdClass; use \\RecursiveDirectoryIterator; use \\RecursiveIteratorIterator; use Phar; use \\PharData;'
      : ''
    ) . $vendorNamespaces .
    (str_starts_with($finalContent, PHP_OPEN_TAG_STRING)
      ? preg_replace($patternRemoveUse, '', mb_substr($finalContent, PHP_OPEN_TAG_LENGTH))
      : preg_replace($patternRemoveUse, '', ' ' . PHP_END_TAG_STRING . $finalContent)
    );
}
