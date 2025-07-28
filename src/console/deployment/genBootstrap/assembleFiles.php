<?php
declare(strict_types=1);
namespace otra\console\deployment\genBootstrap;
use otra\OtraException;
use PDO;
use const otra\cache\php\{BASE_PATH, BUNDLES_PATH, CLASSMAP, CONSOLE_PATH, CORE_PATH};
use const otra\console\{CLI_INFO_HIGHLIGHT,CLI_WARNING, END_COLOR};

const
  PATTERN = '@\s*
      (?<!//)(?<!//\s)
      (?:(?:\brequire|\binclude)(?:_once)?\s[^;]+;\s*)|
      (?:\bextends\s[^\{]+\s*)|
      (?:->renderView\s*\([^\),]+)
      @mx',
  STRLEN_LABEL_CONST = 6,
  LABEL_CONST = 'const ',
  OTRA_LABEL_INCLUDE = 'include',
  OTRA_LABEL_REQUIRE = 'require';

/**
 * We retrieve file names to include that are found via the use statements to $filesToConcat in php/use category.
 * We then clean the use keywords ...
 * This function only evaluates ONE use statement at a time.
 *
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
 * }                $filesToConcat   Files to parse after have parsed this one
 * @param string[]  $parsedFiles     Remaining files to concatenate
 * @param string[]  $parsedConstants
 * @param string[]  $parsedClasses   Classes already used. It helps to resolve name conflicts.
 *
 * @return array<string[],array<string,string>,array<string,string>> [$classesFromFile, $replacements]
 */
function getFileNamesFromUses(
  int $level,
  string $contentToAdd,
  array &$filesToConcat,
  array &$parsedFiles,
  array &$parsedConstants,
  array &$parsedClasses
) : array
{
  // Match all use statements
  // We need to use * and / in the characters to avoid to prevent comments containing 'use' to be taken into account
  preg_match_all(
    '@(?<!//|/\*)\s*\buse\s+([^/*;()]*);\s*$@m',
    $contentToAdd,
    $useMatches,
    PREG_OFFSET_CAPTURE
  );

  // Return early if no matches found
  if (empty($useMatches[1]))
    return [[], [], []];
  
  $conflictReplacements = $classesFromFile = $replacements = [];

  foreach ($useMatches[1] as $useMatch)
  {
    $chunks = explode(',', str_replace(["\n", "\r"], '', $useMatch[0]));
    $isConst = str_starts_with($useMatch[0], LABEL_CONST);

    // Skip function imports
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
        $beginString = mb_substr($chunk, 0, $posLeftParenthesis); // e.g., otra\otra\bdd\

        // originalChunk is used to determine the FQCN that will be displayed if the class comes from an external
        // library class
        if ($originalChunk === '' && $chunk !== '')
          $originalChunk = $beginString;

        $lastChunk = mb_substr($chunk, $posLeftParenthesis + 1); // e.g., Sql

        if ($isConst)
          $constantName = $lastChunk;

        $classToReplace = $beginString . str_replace(' ', '', $lastChunk);

        // If it's a vendor, we keep the class?
        if (!isset(CLASSMAP[$classToReplace]) || str_contains(CLASSMAP[$classToReplace], 'vendor'))
        {
          // simplifies the usage of classes by transitioning from FQCN to simple class names ... otra\bdd\Sql => Sql
          $replacements[$classToReplace] = $lastChunk;
        } else
        {
          if ($classToReplace[0] !== '\\')
            $classToReplace = '\\' . $classToReplace;

          // - \b: Matches a word boundary, ensuring we only match whole words.
          // - preg_quote($lastChunk, '/'): Escapes special regex characters in the class name.
          // - (?![\\\\\w]): Negative lookahead to ensure the match isn't followed by a backslash or word character,
          // preventing replacement in class names (e.g. `class \Stripe\StripeWebhookHandlerAction\`)
          $replacements['@\b' . preg_quote($lastChunk, '/') . '(?![\\\\\w])@'] = $classToReplace;
        }

        // case use xxx\xxx{XXX}; (notice that there is only one name between the braces
        if ($classToReplace[-1] === '}')
          $classToReplace = mb_substr($classToReplace, 0, -1);

        $lastClassSegment = $lastChunk;
      } else
      {
        /** If we have a right parenthesis, we strip it and put the content before the beginning of the use statement.
         * Otherwise ... if it uses a PHP7 shortcut with parenthesis, we add the beginning of the use statement.
         * Otherwise ... we just put the string directly */
        if (!str_contains($chunk, '}'))
        {
          /** @var string $lastChunk */
          if ('' === $beginString) // case use xxx\xxx\xxx;
          {
            $classToReplace = $chunk;
            $tempChunks = explode(NAMESPACE_SEPARATOR, $classToReplace);

            if ($isConst)
              $constantName = end($tempChunks);

            $lastChunk = array_pop($tempChunks);

            if (in_array($lastChunk, $parsedClasses))
            {
              $lastChunkWithoutConflictIndex = $lastChunk;
//              $lastChunk .= '1';
            }

            // replaces `use examples\deployment\fixFiles\input\vendor\TestTrait;` by `use TestTrait;`
            if (str_contains($classToReplace, 'Trait'))
            {
              // PHP_EOL to avoid that use is being put in a previous comment that would be removed afterward
              $replacements['@(?<![$\\\\])\buse\s+' . preg_quote($classToReplace, '@') . '\b(?![\\\\\w()]);@'] = 
                PHP_EOL . 'use ' . $lastChunk . ';';
            } elseif (str_contains($classToReplace, 'const') || str_contains($classToReplace, 'function'))
            {
              $replacements['@(?<![\$\\\\])\buse\s+' . preg_quote($classToReplace, '@') . '\b(?![\\\\\w()]);@'] =
                '';
            } else
            {
              // Always replace it by the short name of the class
              $replacements['@(?<![\$\\\\])\b' . preg_quote($classToReplace, '@') . '\b(?![\\\\\w])@'] =
                $lastChunk;

              if (isset($lastChunkWithoutConflictIndex))
                $conflictReplacements[$lastChunkWithoutConflictIndex] = $lastChunk;
            }
          } else // case use xxx\xxx{xxx, XXX, xxx}; (notice the uppercase, it's where we are)
          {
            $classToReplace = $beginString . $chunk;

            if ($isConst)
              $constantName = $chunk;

            // If it's a vendor, we keep the class?
            if (!isset(CLASSMAP[$classToReplace]) || str_contains(CLASSMAP[$classToReplace], 'vendor'))
            {
              // simplifies the usage of classes by transitioning from FQCN to simple class names ... otra\bdd\Sql => Sql
              $replacements[$classToReplace] = $chunk;
            } else
            {
              if ($classToReplace[0] !== '\\')
                $classToReplace = '\\' . $classToReplace;

              $replacements['@' . $lastChunk . '(?![\\\\])@'] = $classToReplace;
            }
          }

          $lastClassSegment = $lastChunk;
          $lastChunk = $chunk;
        } else
        { // case use xxx/xxx{xxx, xxx, XXX}; (notice the uppercase, it's where we are)
          $lastChunk = mb_substr($chunk, 0, -1);
          $classToReplace = $beginString . $lastChunk;

          if ($isConst)
            $constantName = $lastChunk;

          // If it's a vendor, we keep the class?
          if (!isset(CLASSMAP[$classToReplace]) || !str_contains(CLASSMAP[$classToReplace], 'vendor'))
          {
            // simplifies the usage of classes by transitioning from FQCN to class names ... otra\bdd\Sql => Sql
            $replacements[$classToReplace] = $lastChunk;
          }

          $lastClassSegment = $lastChunk;
        }
      }

      // Example of fully qualified constant name => otra\services\OTRA_KEY_STYLE_SRC_DIRECTIVE
      $fullyQualifiedConstantName = $originalChunk;

      if ($originalChunk !== '' && !str_ends_with($originalChunk, NAMESPACE_SEPARATOR))
        $fullyQualifiedConstantName .= NAMESPACE_SEPARATOR;

      $fullyQualifiedConstantName .= $lastChunk;

      if ($isConst)
        $parsedConstants[$constantName] = substr($fullyQualifiedConstantName, STRLEN_LABEL_CONST);
      else
      {
        // We analyze the use statement to retrieve the name of each class which is included in it.
        require_once CONSOLE_PATH . 'deployment/genBootstrap/analyzeUseToken.php';
        analyzeUseToken(
          $level,
          $filesToConcat,
          $classToReplace,
          $parsedFiles,
          $fullyQualifiedConstantName,
          $parsedClasses,
          $lastClassSegment
        );

        // The classes will be useful when analyzing the extends statements
        $classesFromFile[] = $classToReplace;
      }
    }

    // remove this variable as we use an `isset` on this at some point in the `foreach` loop.
    unset($lastChunkWithoutConflictIndex);
  }

  return [$classesFromFile, $replacements, $conflictReplacements];
}

/**
 * Retrieves information about what kind of file inclusion we have (include, require), the related code and its position.
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
function getDependenciesFileInfo(array &$parameters) : void
{
//  list($level, $contentToAdd, $filename, $filesToConcat, &$parsedFiles, $classesFromFile, &$parsedConstants) = $parameters;
  [
    'level' => $level,
    'contentToAdd' => $contentToAdd,
    'filename' => $filename,
    'filesToConcat' => &$filesToConcat,
    'parsedFiles' => &$parsedFiles,
    'classesFromFile' => $classesFromFile,
    'parsedConstants' => &$parsedConstants
  ] = $parameters;

  preg_match_all(PATTERN, $contentToAdd, $matches, PREG_OFFSET_CAPTURE);

  // For all the inclusions
  foreach($matches[0] as $match)
  {
    $cannotIncludeFile = false;

    if ('' === $match[0])
      continue;

    $trimmedMatch = trim(preg_replace('@\s+@', ' ', $match[0]));
    /** WE RETRIEVE THE CONTENT TO PROCESS, NO TRANSFORMATIONS HERE */

    /** REQUIRE OR INCLUDE STATEMENT EVALUATION */
    if (str_contains($trimmedMatch, OTRA_LABEL_REQUIRE) || str_contains($trimmedMatch, OTRA_LABEL_INCLUDE))
    {
      // If we find 'require myFile.php' in the file 'tools.php' then it is a `require` in a comment so no need to
      // process it
      if ($filename === CORE_PATH . 'console/tools.php' && str_contains($trimmedMatch, 'myFile'))
        continue;

      require_once CONSOLE_PATH . 'deployment/genBootstrap/getFileInfoFromRequireMatch.php';
      [$tempFile, $isTemplate, $cannotIncludeFile] = getFileInfoFromRequireMatch($trimmedMatch, $filename);

      if ($cannotIncludeFile)
        continue;

      /* We are making an exception for this specific `require` statement because
         it is used by the production controller and relates to a template.
         So there's no need to include it since
         the management of HTML templates is not fully operational at the moment. */
      if (!$isTemplate) // if the file to include is not a template
      {
        // If we find __DIR__ in the include/require statement, then we replace it with the good folder and not the
        // actual folder (...console ^^)
        $posDir = strpos($tempFile, '__DIR__');

        if ($posDir !== false)
          $tempFile = substr_replace(
            $tempFile,
            '\'' . dirname($filename) . '/\'',
            $posDir,
            7 // 7 is the length of the string '__DIR__'
          );

        // we must not change these inclusions from
        // - CORE_PATH . Router.php
        // - security configuration
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
        // Here we handle the namespace issues (if we were not using namespaces, we would not need this condition)
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

          foreach (array_keys($parsedConstants) as $constantString)
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

        // Ugly temporary fix for dynamic inclusions via the `self` keyword
        if (str_contains($tempFile, 'self::'))
          continue;

        require_once CONSOLE_PATH . 'deployment/genBootstrap/resolveInclusionPath.php';
        $tempFile = resolveInclusionPath($tempFile);

        // we must not take care of the bundles/config/Config.php as it is an optional config file.
        if ($tempFile === BUNDLES_PATH . 'config/Config.php')
          continue;

        // We exclude inclusions that have `$` from the warning
        // as the variable could have the beginning the path of the absolute path
        if (VERBOSE > 0
          && $tempFile[0] !== '/'  
          && !str_contains($tempFile, BASE_PATH)
          && !str_contains($tempFile, BUNDLES_PATH)
          && !str_contains($tempFile, 'SECRETS_FILE')
        )
        {
          echo PHP_EOL, CLI_WARNING, 'BEWARE, you have to use absolute path for files inclusion! \'' . $tempFile,
          '\' in ', $filename, '.', PHP_EOL,
          'Ignore this warning if your path is already absolute or your file is outside the project folder.',
          END_COLOR, PHP_EOL;
        }

        if (!file_exists($tempFile) && !str_contains($tempFile, 'SECRETS_FILE'))
        {
          echo PHP_EOL, CLI_WARNING, 'OTRA cannot process this ', CLI_INFO_HIGHLIGHT, $trimmedMatch, CLI_WARNING,
            PHP_EOL,
            ' => ', CLI_INFO_HIGHLIGHT, $tempFile, CLI_WARNING, ' in ', CLI_INFO_HIGHLIGHT, $filename, CLI_WARNING,
            '!', PHP_EOL,
            'Maybe the file does not exist or it\'s a dynamic inclusion.', END_COLOR, PHP_EOL, PHP_EOL;
        }

        if ($cannotIncludeFile)
          continue;

        if (!isset($filesToConcat['php']))
        {
          $filesToConcat['php'] = [
            'use' => [],
            OTRA_KEY_REQUIRE => [],
            OTRA_KEY_EXTENDS => [],
            OTRA_KEY_STATIC => []
          ];
        }

        $infos = ['match' => $match[0]];

        if (in_array($tempFile, $parsedFiles, true)
//          && (str_contains($trimmedMatch, 'require_once')
//            || str_contains($trimmedMatch, 'include_once')
//            || str_contains($tempFile, CORE_PATH))
        )
          continue;
        
        $filesToConcat['php'][OTRA_KEY_REQUIRE][$tempFile] = $infos;
        
      } else
      {
        $fileType = str_contains($trimmedMatch, 'renderView') ? 'renderView' : 'randomTemplate';

        if (!isset($filesToConcat[$fileType]))
          $filesToConcat[$fileType] = [];

        require_once CONSOLE_PATH . 'deployment/genBootstrap/resolveInclusionPath.php';
        $filesToConcat[$fileType][OTRA_KEY_REQUIRE][resolveInclusionPath($tempFile)] = ['match' => $match[0]];
      }
    } elseif(str_contains($trimmedMatch, OTRA_KEY_EXTENDS))
    { // Extends block is only tested if the class has not been loaded via a use statement before

      // Extracts the file name in the extends statement ... (8 = strlen('extends '))
      $class = mb_substr($trimmedMatch, 8);

      // if the class begin by \, then it is a standard class, and then we do nothing
      if (NAMESPACE_SEPARATOR === $class[0])
        continue;

      require_once CONSOLE_PATH . 'deployment/genBootstrap/searchForClass.php';
      $tempFile = searchForClass($classesFromFile, $class, $contentToAdd, $match[1]);

      // If we already have included the class
      if (false === $tempFile)
        continue;

      if (in_array($tempFile, $parsedFiles))
      {
        if (VERBOSE > 1)
          showFile($level, $tempFile, ' via extends' . OTRA_ALREADY_PARSED_LABEL);

        continue;
      }

      $filesToConcat['php'][OTRA_KEY_EXTENDS][] = $tempFile;
    }

    /** @var ?string $tempFile */
    // if we have to add a file that we don't have yet...
    if (isset($tempFile)
//      &&
//      (str_contains($trimmedMatch, 'require_once')
//        || str_contains($trimmedMatch, 'include_once')
//        || str_contains($trimmedMatch, OTRA_KEY_EXTENDS)
//        || str_contains($trimmedMatch, CORE_PATH))
    )
      $parsedFiles[] = $tempFile;
  }
}

/**
 * We change things like \one\particular\namespace::trial() by namespace::trial() and we include the related files
 *
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
    '@
  (?:^|[^/])                           # Start of line or not preceded by a slash (to avoid single-line comments)
  (?:                                  # Non-capturing group for comments
    (?!//[^\n]*|/\*(?:(?!\*/).)*?\*/)  # Negative lookahead to exclude comments
  )
  (\\\\?(?:\\w+\\\\)*)                 # Capture the namespace (optional)
  ((\\w+):{2}\\$?\\w+)                 # Capture the static call
  (?![^/]*\*/)                         # Negative lookahead to ensure we\'re not in a multi-line comment
@xs',
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
    /** @var int $offset */
    [$classPath, $offset] = $match[1];
    $classAndFunction = $match[2][0];
    $class = $match[3][0];

    // match[1][0] is like \xxx\yyy\zzz\
    // match[2][0] is like class::function
    // match[3][0] is like class

    // no need to include self or parent !!
    // renderController is an edge case present in OtraException.php
    // PDO is a native class, no need to import it!
    if (in_array($class, ['self', 'static', 'parent', 'renderController', PDO::class]))
      continue;

    // str_replace to ensure us that the same character is used each time
    $newFile = BASE_PATH . str_replace(NAMESPACE_SEPARATOR, '/', $classPath . $class . '.php');

    // does the class exist ? if not, we search the real path of it
    if (!file_exists($newFile))
    {
      require_once CONSOLE_PATH . 'deployment/genBootstrap/searchForClass.php';
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
      showFile($level, $newFile, ' via static direct call' . OTRA_ALREADY_PARSED_LABEL);

    // We have to readjust the found offset each time with $lengthAdjustment 'cause we change the content length by
    // removing content
    $length = mb_strlen($classPath . $classAndFunction);
    $contentToAdd = substr_replace($contentToAdd,
      $classAndFunction,
      $offset - $lengthAdjustment,
      $length
    );

    // We calculate the new offset for the offset!
    $lengthAdjustment += $length - mb_strlen($classAndFunction);
  }
}

/**
 * @param array<int, array{
 *   actualId : int,
 *   children: ?array,
 *   increment: int,
 *   level: int,
 *   filename: string,
 *   contentToAdd: string,
 *   outside: string,
 *   parent: int,
 *   parsedFiles: string[],
 *   parsedConstants: string[],
 *   route: string,
 *   routeManagement: bool,
 *   tempOutsideContent: string,
 *   linting: bool,
 *   temporaryPhpRouteFile: string
 * }> $taskData
 *
 * @throws OtraException
 * @return array<int, array{
 *   actualId : int,
 *   bundle: string,
 *   children: ?array,
 *   increment: int,
 *   level: int,
 *   filename: string,
 *   contentToAdd: string,
 *   outside: string,
 *   parent: int,
 *   parsedFiles: string[],
 *   parsedConstants: string[],
 *   linting: bool,
 *   route: string,
 *   routeManagement: bool,
 *   temporaryPhpRouteFile: string
 * }> $taskData
 */
function assembleFiles(
  int $parent,
  array &$taskData,
  string $partialContent
) : array
{
  if (!isset($taskData[$parent]['children']))
    $taskData[$parent]['children'] = [];

  if (0 === $taskData[0]['level'])
  {
    if (VERBOSE > 1)
    {
      require_once CONSOLE_PATH . 'deployment/genBootstrap/showFile.php';
      showFile($taskData[0]['level'], $taskData[0]['filename']);
    }
  }

  ++$taskData[0]['level'];

  // $filesToConcat will allow us to know which files are remaining to parse
  $filesToConcat = [];
  [$classesFromFile, $replacements, $conflictReplacements] = getFileNamesFromUses(
    $taskData[0]['level'],
    $partialContent,
    $filesToConcat,
    $taskData[0]['parsedFiles'],
    $taskData[0]['parsedConstants'],
    $taskData[0]['parsedClasses']
  );

  unset($search, $replace, $conflictReplacements);

  $taskData[0]['replacements'] = array_merge($taskData[0]['replacements'], $replacements);

  $toPassAsReference = [
    'level' => $taskData[0]['level'],
    'contentToAdd' => $partialContent,
    'filename' => $taskData[0]['filename'],
    'filesToConcat' => $filesToConcat,
    'parsedFiles' => $taskData[0]['parsedFiles'],
    'classesFromFile' => $classesFromFile,
    'parsedConstants' => $taskData[0]['parsedConstants']
  ];
  
  // REQUIRE, INCLUDE AND EXTENDS MANAGEMENT
  getDependenciesFileInfo($toPassAsReference);
  $filesToConcat = $toPassAsReference['filesToConcat'];
  $taskData[0]['parsedFiles'] = $toPassAsReference['parsedFiles'];
  $taskData[0]['parsedConstants'] = $toPassAsReference['parsedConstants'];

  processStaticCalls(
    $taskData[0]['level'],
    $partialContent,
    $filesToConcat,
    $taskData[0]['parsedFiles'],
    $classesFromFile
  );

  if (1 === $taskData[0]['level'])
  {
    [$contentInside, $contentOutside, $useConst, $useFunction, $globalConstants] =
      handlePhpAndHtml($partialContent, $taskData[0]['parsedClasses']);

    $contentOutside = implode('', $contentOutside);

    $partialContent = $contentInside . $contentOutside;

    if ($contentOutside !== '')
    {
      if (!str_starts_with($contentOutside, PHP_OPEN_TAG_STRING))
        $contentOutside = PHP_OPEN_TAG_STRING . ' ' . $contentOutside;

      if (!str_ends_with($contentOutside, PHP_END_TAG_STRING))
        $contentOutside .= PHP_END_TAG_STRING;
    }

    $taskData[0]['contentToAdd'] = preg_replace(
      '@\s*namespace\s+[a-zA-Z0-9\\\\]+\s*;                           # inline namespace
                      |\s*namespace\s+[a-zA-Z0-9\\\\]+\s*\{([^{}]+|\{[^{}]*})*}@mx', // block namespace
      '',
      $partialContent
    );

    $taskData[0]['parent'] = 0;
    $taskData[0]['outside'] = $contentOutside;
    $taskData[0]['inside'] = $contentInside;
    $taskData[0]['inclusionCode'] = '';

    $taskData[0]['use const'][] = is_array($useConst)
      ? implode('', $useConst)
      : $useConst;
    $taskData[0]['use function'][] = is_array($useFunction)
      ? implode('', $useFunction)
      : $useFunction;
    $taskData[0]['global constants'] = array_merge(
      $taskData[0]['global constants'] ?? [],
      $globalConstants
    );
  }

  $syntaxError = false;

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
      // There is no function, so we can include that multiple times with no issues. No need of `require_once` then!
      // If we put require_once, it will prevent detecting nested inclusions.
      require CONSOLE_PATH . 'deployment/genBootstrap/processFile.php';
    }
  }

  // We increase the process step (DEBUG)
  ++$taskData[0]['increment'];

  // Either we begin the process, either we return to the roots of the process so ... (DEBUG)
  --$taskData[0]['level'];

  return $taskData;
}
