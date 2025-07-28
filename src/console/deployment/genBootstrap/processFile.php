<?php
declare(strict_types=1);
namespace otra\console\deployment\genBootstrap;
use otra\OtraException;
use const otra\cache\php\{APP_ENV, BASE_PATH, CACHE_PATH, CONSOLE_PATH, CORE_PATH};
use const otra\console\{CLI_WARNING, END_COLOR};
/**
 * @var int $actualId
 * @var array<int, array{
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
 * }> $taskData 
 * @var string $partialContent
 * @var array{
 *   use ?: string[],
 *   require ?: array<string,array{
 *   match: string,
 *   posMatch: int
 *   }>,
 *   extends ?: string[],
 *   static ?: string[]
 * } | string[] $entries
 * @var array{
 *   php:array{
 *     use ?: string[],
 *     require ?: array<string,array{
 *       match: string,
 *       posMatch: int
 *     }>,
 *     extends ?: string[],
 *     static ?: string[]
 *   },
 *   template: array
 * } $filesToConcat Files to parse after have parsed this one
 * @var string $fileType
 * @var string $inclusionMethod
 * @var int $parent
 */
foreach ($entries as $inclusionMethod => $fileEntries)
{
  // $nextFileOrInfo is an array only if we are in a require/include statement
  foreach ($fileEntries as $keyOrFile => $nextFileOrInfo)
  {
    $actualId = ++$taskData[0]['actualId'];
    $taskData[$actualId]['id'] = $actualId;
    $taskData[$actualId]['parent'] = $parent;
    $taskData[$parent]['children'][] = $actualId;

    if ($taskData[0]['level'] === 0 && array_key_first($filesToConcat) === $fileType)
      $taskData[$actualId]['outside'] = $partialContent;
    
    // We increase the process step (DEBUG)
    ++$taskData[0]['increment'];

    /* @var string $method */ 
    if ($fileType === 'renderView')
    {
      $tempFile = $keyOrFile;
      $method = ' via renderView';
    } elseif ($fileType === 'template')
    {
      $tempFile = $keyOrFile;
      $method = ' via require/include statement';
    } else
    {
      switch($inclusionMethod)
      {
        case 'use':
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
    }

    /** @var string $tempFile In all cases, this is a string,
     *                        redundant PHPDoc but Psalm does not understand otherwise */
    // If it is a class from an external library (not from the framework),
    // we let the inclusion code, and we do not add the content to the bootstrap file.
    if (str_contains($tempFile, 'vendor') && !str_contains($tempFile, 'otra'))
    {
      // It can be a SwiftMailer class, for example
      echo CLI_WARNING, 'EXTERNAL LIBRARY', ' : ', $tempFile, END_COLOR, PHP_EOL;
      unset($filesToConcat[$fileType][$inclusionMethod][$tempFile]);
      continue;
    }
    
    if ($fileType === TYPE_VALUE_PHP)
    {
      // Files already loaded by default will not be added
      if ($taskData[0]['filename'] !== CORE_PATH . 'Router.php'
        && ($tempFile === BASE_PATH . 'config/Routes.php' || $tempFile === CORE_PATH . 'Router.php'))
      {
        echo CLI_WARNING, 'This file will be already loaded by default for each route : ' .
          substr($tempFile, BASE_PATH_LENGTH), END_COLOR, PHP_EOL;
        unset($filesToConcat[$fileType][$inclusionMethod][$tempFile]);
        continue;
      }

      // The file `CORE_PATH . templating/blocks.php` needs an alias for `CORE_PATH . MasterController.php` so we have
      // to handle that case
      if ($tempFile === CACHE_PATH . 'php/MasterController.php')
        $tempFile = CORE_PATH . 'MasterController.php';

      if ($tempFile === 'otra\tools\secrets\SECRETS_FILE.\'\'')
        $tempFile = CACHE_PATH . 'php/' . $_SERVER[APP_ENV] . 'Secrets.php';
    }

    $nextContentToAdd = file_get_contents($tempFile);

    // update the current name as it is used for debugging like in assembleFiles::getDependenciesFileInfo()
    $taskData[0]['filename'] = $tempFile;

    if ($fileType === TYPE_VALUE_PHP)
      $nextContentToAdd .= PHP_END_TAG_STRING;

    $contentPartsToAdd = '';
    $isReturn = false;

    if (OTRA_KEY_REQUIRE === $inclusionMethod)
    {
      $requirePosition = strpos($partialContent, $nextFileOrInfo['match']);

      // If it's not related to the `renderView` controller function call, then it can return a result other than a
      // string
      if ($fileType !== 'renderView')
      {
        // Determining whether the included file returns something or not
        $tokens = token_get_all($nextContentToAdd);
        $inDeclare = false;
        $returnContent = '';
        $depth = 0; // Track the nesting level

        foreach ($tokens as $token)
        {
          if (!is_array($token))
          {
            if ($isReturn && $depth === 0)
            {
              $returnContent .= $token;

              if ($token === ';')
                break;
            }
            if ($token === '{') ++$depth;
            if ($token === '}') --$depth;
            continue;
          }

          switch ($token[0])
          {
            case T_FUNCTION:
            case T_CLASS:
            case T_TRAIT:
            case T_INTERFACE:
              ++$depth;
              break;
            case T_RETURN:
              if ($depth === 0) $isReturn = true;
              break;
            case T_DECLARE:
              $inDeclare = true;
              break;
            case ';':
              if ($isReturn && $depth === 0)
                break 2;
            // fallthrough
            case T_NAMESPACE:
              $inDeclare = false;
              break;
            default:
              if ($isReturn && $depth === 0)
                $returnContent .= $token[1];
              elseif ($inDeclare && ($token[0] === T_STRING || $token[0] === T_LNUMBER))
              {
                $lowercaseToken = strtolower($token[1]);

                if ($lowercaseToken === 'strict_types' || $lowercaseToken === '1')
                  continue 2;
              }
          }
        }

        unset($inDeclare, $depth, $tokens);

        // if the inclusion statement has not already been removed (because already loaded via another way)
        if ($requirePosition !== false)
        {
          /* if the file has contents that begin with a return statement and strict type declaration, then we apply a
             particular process*/
          if ($isReturn)
          {
            require_once CONSOLE_PATH . 'deployment/genBootstrap/processReturn.php';
            /** @var array{match:string,posMatch:int} $nextFileOrInfo $isReturn */

            $taskData[$actualId] = [
              'parent' => $actualId,
              'inclusionCode' => processReturn(
                $partialContent,
                $returnContent,
                $nextFileOrInfo['match'],
                $requirePosition
              ),
              'inside' => $returnContent
            ];
          } else
          {
            // if the key 'extends' is set, it is always true
            if (isset($nextFileOrInfo['extends']))
            {
              $contentOutside = [];
              $contentInside = $useConst = $useFunction = $globalConstants = '';
            } else
            {
              [$contentInside, $contentOutside, $useConst, $useFunction, $globalConstants] =
                handlePhpAndHtml($nextContentToAdd, $taskData[0]['parsedClasses']);
            }

            $contentOutside = implode('', $contentOutside);

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

            $taskData[$actualId] = [
              'parent' => $actualId,
              'inclusionCode' => $nextFileOrInfo['match'],
              'outside' => $contentOutside,
              'inside' => $contentInside
            ];

            $taskData[$actualId]['use const'][] = is_array($useConst)
              ? implode('', $useConst)
              : $useConst;
            $taskData[$actualId]['use function'][] = is_array($useFunction)
              ? implode('', $useFunction)
              : $useFunction;
            $taskData[$actualId]['global constants'] = array_merge(
              $taskData[$actualId]['global constants'] ?? [],
              $globalConstants
            );
          }
        }
      }
    } else
    {
      $taskData[$actualId] = [
        'parent' => $actualId,
        'outside' => preg_replace([NAMESPACE_PATTERN, DECLARE_PATTERN], ['', ' '], $nextContentToAdd)
      ];
    }

    if (VERBOSE > 1)
      showFile($taskData[0]['level'], $tempFile, $method);

    if ($taskData[0]['linting'])
    {
      file_put_contents(
        $taskData[0]['temporaryPhpRouteFile'],
        lastAdjustments(
          $taskData[0]['bundle'],
          $taskData[0]['route'],
          $taskData[0]['routesManagement'],
          $taskData[0]['parsedConstants'],
          finalizeCodeAssembly($taskData))
      );

      if (hasSyntaxErrors($taskData[0]['temporaryPhpRouteFile']))
      {
        echo 'Last file checked: ' . $taskData[0]['filename'], PHP_EOL;
        throw new OtraException(code: 1, exit: true);
      }
    }

    // If we have a "return type" PHP file, then the file has already been included before,
    // in the 'processReturn' function
    if (!$isReturn)
      assembleFiles($actualId, $taskData, $nextContentToAdd);

    unset($filesToConcat[$fileType][$inclusionMethod][$tempFile]);
  }
}
