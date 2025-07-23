<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\deployment
 */
declare(strict_types=1);

namespace otra\console\deployment\genBootstrap;

use otra\OtraException;
// do not delete CORE_VIEWS_PATH and DIR_SEPARATOR without testing as they can be used via eval()
use const otra\cache\php\{BASE_PATH, BUNDLES_PATH, CONSOLE_PATH, CORE_PATH};
use const otra\console\{CLI_ERROR, CLI_INFO, CLI_SUCCESS, END_COLOR};
use function otra\console\showContextByError;

require_once CONSOLE_PATH . 'tools.php';

const
  // We need to use * and / in the characters to avoid preventing comments containing 'use' to be taken into account
  PATTERN_REMOVE_USE = '@
    ^(?!.*//.*\buse\b)           # Ensures the line does not contain a comment with "use"
    (?<!//|/\*)                  # Ensures we are not inside a comment
    \s*                          # Optional spaces
    (\buse\s+                    # "use" keyword followed by spaces
    (?![^;]*(?:trait\b|Trait\b)) # Negative lookahead to avoid trait use statements
    (?:function\s+)?             # Optional "function" for function imports
    [^*/();]+                      # Anything except */, or ;
    ;)                           # Final semicolon
    \s*\n?                       # Optional spaces and newline
  @xm',
  LOADED_DEBUG_PAD = 80,
  PHP_OPEN_TAG_STRING = '<?php',
  PHP_OPEN_TAG_LENGTH = 5,
  PHP_OPEN_TAG_AND_SPACE_LENGTH = 6,
  PHP_END_TAG_LENGTH = 2,
  
  PHP_END_TAG_STRING = '?>',

  OTRA_ALREADY_PARSED_LABEL = ' ALREADY PARSED',
  OTRA_KEY_REQUIRE = 'require',
  OTRA_KEY_EXTENDS = 'extends',
  OTRA_KEY_STATIC = 'static',

  NAMESPACE_SEPARATOR = '\\';
  define(__NAMESPACE__ . '\\BASE_PATH_LENGTH', strlen(BASE_PATH));

function hasSyntaxErrors(string $phpFile) : bool
{
  // Syntax verification, 2>&1 redirects stderr to stdout
  exec(PHP_BINARY . ' -l ' . $phpFile . ' 2>&1', $output);
  $output = implode(PHP_EOL, $output);

  if (mb_strlen($output) <= 6 || false === mb_strpos($output, 'pars', 7))
    return false;

  echo PHP_EOL, CLI_ERROR, $output, PHP_EOL, PHP_EOL;
  showContextByError($phpFile, $output, 10);

  return true;
}

/**
 * Puts the final contents into a temporary file.
 * Checks syntax errors.
 * If all was ok, it retrieves the contents of the temporary file in the real final file.
 * Then it checks namespaces errors.
 * If all was ok, it deletes the temporary file.
 *
 * @throws OtraException
 */
function contentToFile(string $content, string $outputFile) : void
{
  if (VERBOSE > 0)
    echo PHP_EOL;

  echo PHP_EOL, CLI_INFO, 'FINAL CHECKINGS => ';
  /* Do not suppress the indented lines. They allow testing namespaces problems. We put the file in another directory
     to see if namespaces errors are declared at the normal place and not at the temporary place */
  $tempFile = BASE_PATH . 'logs/temporary file.php';
  file_put_contents($tempFile, $content);

  // Test each part of the process to precisely detect where there is an error.
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
 * Assembles the final code structure based on the provided data.
 *
 * @param array<int, array{
 *   id: int,
 *   actualId: int,
 *   bundle: string,
 *   increment: int,
 *   level: int,
 *   filename: string,
 *   outside: string,
 *   parent: null|int,
 *   parsedFiles: array<string, mixed>,
 *   parsedConstants: array<string, mixed>,
 *   linting: bool,
 *   route: string,
 *   routesManagement: bool,
 *   temporaryPhpRouteFile: string
 * }> $filesToProcess
 *
 * @return string
 */
function finalizeCodeAssembly(array $filesToProcess) : string
{
  $finalContent = $topOfContent = '';
  $stack = [0]; // We begin by the root identifier
  $globalConstants = $visited = [];

  while (!empty($stack))
  {
    $currentId = array_pop($stack);

    if (isset($visited[$currentId]))
      continue;

    $visited[$currentId] = true;
    $fileToProcess = $filesToProcess[$currentId];
    $outsideContent = $fileToProcess['outside'] ?? '';

    if ($outsideContent !== null)
    {
      $startsWithTag = str_starts_with($outsideContent, PHP_OPEN_TAG_STRING);
      $endsWithTag = str_ends_with($outsideContent, PHP_END_TAG_STRING);

      if ($startsWithTag || $endsWithTag)
      {
        $start = $startsWithTag ? PHP_OPEN_TAG_AND_SPACE_LENGTH : 0;
        $length = strlen($outsideContent) - ($endsWithTag ? PHP_END_TAG_LENGTH : 0);
        $outsideContent = substr($outsideContent, $start, $length - $start);
      }

      $finalContent = $outsideContent . $finalContent;
      // remove use statements
      $finalContent = preg_replace_callback(
        '@
    (?://.*|/\*.*?\*/) # Match comments
    |                  # OR
    (\s*\buse\s+       # "use" keyword with spaces
    (?![^;]*\btrait|Trait\b) # Negative lookahead to exclude traits
    [^();]+;)            # Rest of the use statement
  @sx',
        function ($matches) 
        {
          // If it's a comment or if the capturing group is empty, keep it intact
          if (empty($matches[1]))
            return $matches[0];

          // Check if the 'use' statement is not within a class or trait context
          if (preg_match('/\bclass\b|\btrait|Trait\b/', $matches[0]))
            return $matches[0];

          // If it's a 'use', remove it
          return '';
        },
        $finalContent
      );
    }

//    if (!isset($fileToProcess['global constants']))
//      $fileToProcess['global constants'] = '';

//    $topOfContent = (is_array($fileToProcess['global constants'])
//      ? implode('', $fileToProcess['global constants'])
//      : $fileToProcess['global constants']) . $topOfContent;

    if (isset($fileToProcess['global constants']))
    {
      $globalConstants = array_merge($globalConstants, $fileToProcess['global constants']);
    }

    // Something to put to replace the inclusion statement?
    if (isset($fileToProcess['inside']))
    {
      $trimmedInclusionCode = trim($fileToProcess['inclusionCode']);
      $inclusionPosition = strpos($finalContent, $trimmedInclusionCode);

      if ($inclusionPosition !== false)
      {

        $finalContent = substr_replace(
          $finalContent,
          $fileToProcess['inside'],
          $inclusionPosition,
          strlen($trimmedInclusionCode)
        );
      }
    }

    // Add the children to the stack in the reverse order to process in the right order
    if (isset($fileToProcess['children']))
    {
      foreach ($fileToProcess['children'] as $childId)
      {
        $stack[] = $childId;
      }
    }
  }
  
  $finalConstants = '';

  if (!empty($globalConstants))
  {
    $finalConstants .= 'const ';

    foreach ($globalConstants as $globalConstantKey => $globalConstantValue)
    {
      $finalConstants .= $globalConstantKey . '=' . $globalConstantValue . ',';
    }

    $finalConstants = substr($finalConstants,0,-1) . ';';
  }
  
  $finalContent = $finalConstants . $topOfContent . $finalContent;

  // Now that we have retrieved the files to include, we can clean all the use statements but those are related to traits
  $filesToProcess[0]['replacements']['@
    ^                           # Start of the line
    \s*                         # Optional leading whitespace
    use\s+                      # "use" keyword followed by spaces
    (?!                         # Negative lookahead
      .*?                       # Any characters (non-greedy)
      (?:Trait|trait)\b         # "Trait" or "trait" as a whole word
    )                           # End of negative lookahead
    [\w\\\\]+                   # Namespace and class name
    (?:\s+as\s+\w+)?            # Optional alias
    \s*;                        # Semicolon with optional spaces
    \s*$                        # End of line
  @xm'] = '';

  // Apply the replacements that we identified
  foreach ($filesToProcess[0]['replacements'] as $searchKey => $replacement)
  {
    $finalContent = str_starts_with($searchKey, '@')
      ? preg_replace($searchKey, $replacement, $finalContent)
      : str_replace($searchKey, $replacement, $finalContent);
  }

  return $finalContent . PHP_EOL;
}

function lastAdjustments(
  string $bundle,
  string $route,
  bool $routesManagement,
  array $parsedConstants,
  string $finalContent
) : string
{
  /** We remove all the `declare(strict_types=1);` declarations */
  $finalContent = str_replace(
    [
      'declare(strict_types=1);',
      // We suppress our markers that helped us for the eval()
      'BOOTSTRAP###',
      '###BOOTSTRAP'
    ],
    [''],
    ltrim($finalContent)
  );

  $finalContent = preg_replace(
    [
      '@\?>\s*<\?php@', // We stick the PHP files by removing ending and opening PHP tag between files
      '@(namespace\s[\w\\\\]+\s+)(\{((?:[^}{]*|(?2))*)})@', // Only keep blocks like in `namespace thing { my block }`
      '@\s*namespace [^;]+;\s*@', // We suppress namespaces occurrences in simple cases like  ̀namespace thing;`
      '@\\\\?(PDO(?:Statement)?)@' // We fix PDO and PDOStatement namespaces
    ],
    [
      '',
      PHP_EOL . '$3' . PHP_EOL, // blank lines to prevent, for example, that `// comment` is stuck to `/** comment */` as it is not valid
      PHP_EOL,
      '\\\\$1'
    ],
    $finalContent
  );

  if (!$routesManagement)
  {
    $vendorNamespaceConfigFile = BUNDLES_PATH . $bundle . '/config/vendorNamespaces/' . $route . '.txt';
    // We should not include the additional namespace information in the route management file! (otherwise it's a duplicate)
    $vendorNamespaces = file_exists($vendorNamespaceConfigFile)
      ? file_get_contents($vendorNamespaceConfigFile) . PHP_END_TAG_STRING
      : '';
  } else
    $vendorNamespaces = '';

  $finalContent = str_replace(
    [
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

  // If we have PHP we strip the beginning PHP tag to include it after the PHP code,
  // otherwise we add an ending PHP tag to begin the HTML code.
  // We add the namespace otra\cache\php at the beginning of the file AND we add the security services functions
  // then we delete final ... partial ... use statements taking care of not remove use in words as functions or comments
  // like 'becaUSE'
  return PHP_OPEN_TAG_STRING . ' declare(strict_types=1);' . PHP_EOL .
    'namespace otra\\cache\\php;' .
    (!$routesManagement
      ? 'use \\Exception; use \\Error; use \\Throwable; use \\stdClass; use \\RecursiveDirectoryIterator; use \\RecursiveIteratorIterator; use Phar; use \\PharData; use \\DateTime; use \\Redis;'
      : ''
    ) . $vendorNamespaces . preg_replace(
      PATTERN_REMOVE_USE,
      '',
      (str_starts_with($finalContent, PHP_OPEN_TAG_STRING)
        ? mb_substr($finalContent, PHP_OPEN_TAG_LENGTH)
        : $finalContent)
    );
}

/**
 * Merges files and fixes the usage of namespaces and uses into the concatenated content
 *
 * @param string   $bundle
 * @param string   $route
 * @param string   $content               Content to fix. Generally comes from $fileToInclude.
 * @param int      $verbose
 * @param bool     $linting               If it's true, we regularly check the syntax to be able to stop as soon as there
 *                                      is a problem
 * @param string   $temporaryPhpRouteFile If any error occurs, we put the content in error in this temporary file
 * @param string   $fileToInclude         File to merge.
 * @param bool     $routesManagement
 * @param string[] $parsedFiles
 *
 * @throws OtraException
 * @return array{0:string, 1:string[]}
 */
function fixFiles(
  string $bundle,
  string $route,
  string $content,
  int $verbose,
  bool $linting,
  string $temporaryPhpRouteFile,
  string $fileToInclude = '',
  bool $routesManagement = false,
  array $parsedFiles = []
) : array
{
  if (!defined(__NAMESPACE__ . '\\VERBOSE'))
    define(__NAMESPACE__ . '\\VERBOSE', $verbose);

  // For the moment, as a workaround, we had temporary explicitly added the OtraException file to solve issues.
  $parsedFiles = array_merge($parsedFiles, [CORE_PATH . 'OtraException.php']);
  $parsedConstants = [];

  if ('' !== $fileToInclude)
  {
    require_once CONSOLE_PATH . 'deployment/genBootstrap/assembleFiles.php';
    require_once CONSOLE_PATH . 'deployment/genBootstrap/processFilesTools.php';
    require_once CONSOLE_PATH . 'deployment/genBootstrap/separateInsideAndOutsidePhp.php';
    $taskData = [
      0 =>
      [
        'id' => 0,
        'actualId' => 0,
        'bundle' => $bundle,
        'increment' => 0, // process steps counter (more granular than $level variable)
        'level' => 0, //  depth level of require/include calls
        'filename' => $fileToInclude,
        'outside' => $content,
        'parent' => null,
        'parsedClasses' => [],
        'parsedFiles' => $parsedFiles,
        'parsedConstants' => $parsedConstants,
        'linting' => $linting,
        'replacements' => [],
        'route' => $route,
        'routesManagement' => $routesManagement,
        'temporaryPhpRouteFile' => $temporaryPhpRouteFile
      ]
    ];

    if ($routesManagement)
    {
      $taskData['0']['filename'] = CONSOLE_PATH . 'colors.php';
      $prependedContent = file_get_contents(CONSOLE_PATH . 'colors.php');
      assembleFiles(0, $taskData, $prependedContent);

      $taskData['0']['filename'] = CORE_PATH . 'Logger.php';
      $prependedContent = file_get_contents(CORE_PATH . 'Logger.php');
      assembleFiles(0, $taskData, $prependedContent);

      $taskData['0']['filename'] = CORE_PATH . 'services/securityService.php';
      $prependedContent = file_get_contents(CORE_PATH . 'services/securityService.php');
      assembleFiles(0, $taskData, $prependedContent);

      $taskData['0']['filename'] = $fileToInclude;
    }

    assembleFiles(0, $taskData, $content);
    $finalContent = finalizeCodeAssembly($taskData);
  } else
  {
    preg_match_all(
      '@^\\s*use\\s+[^;]*;\\s*$@mx',
      $content,
      $useMatches,
      PREG_OFFSET_CAPTURE
    );
    $offset = 0;

    foreach($useMatches[0] as $useMatch)
    {
      $length = mb_strlen($useMatch[0]);
      $finalContent = substr_replace($content, '', $useMatch[1] - $offset, $length);
      $offset += $length;
    }
  }

  // we cleanse the temporary file that allows syntax checking as if it exists and
  if (file_exists($temporaryPhpRouteFile))
    unlink($temporaryPhpRouteFile);

  if (VERBOSE > 0)
    echo PHP_EOL;

  echo str_pad('Files to include ', LOADED_DEBUG_PAD, '.'), CLI_SUCCESS, ' [LOADED]', END_COLOR;

  return [lastAdjustments($bundle, $route, $routesManagement, $parsedConstants, $finalContent), $taskData['0']['parsedFiles']];
}
