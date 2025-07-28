<?php
declare(strict_types=1);
namespace otra\console\deployment\genBootstrap;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use SplFileObject;
use const otra\console\{CLI_INFO, CLI_INFO_HIGHLIGHT, END_COLOR};
use const otra\cache\php\CLASSMAP;

const SPECIAL_CLASSES = [
  ReflectionClass::class => true,
  ReflectionException::class => true,
  ReflectionMethod::class => true,
  ReflectionProperty::class => true,
  SplFileObject::class => true,
  'Error' => true,
  'Exception' => true,
  'JetBrains\PhpStorm\Pure' => true,
  'JetBrains\PhpStorm\NoReturn' => true
];

/**
 * We analyze the use statement to retrieve the name of each class which is included in it.
 *
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
 * }               $filesToConcat    Files to parse after have parsed this one
 * @param string[] $parsedFiles      Remaining files to concatenate
 * @param string   $chunk            Original chunk (useful for the external library class warning)
 * @param string[] $parsedClasses    Classes already used. It helps to resolve name conflicts.
 * @param string   $lastClassSegment In `my\super\class2` it will be `class2` 
 */
function analyzeUseToken(
  int $level,
  array &$filesToConcat,
  string $class,
  array &$parsedFiles,
  string $chunk,
  array &$parsedClasses,
  string $lastClassSegment
) : void
{
  $trimmedClass = trim($class);

  // this class is already included in all the cases, no need to have it more than once!
  if ('config\\Router' === $trimmedClass)
    return;

  // dealing with \ at the beginning of the use
  if (!isset(CLASSMAP[$trimmedClass]))
  {
    $revisedClass = mb_substr($trimmedClass, 1);

    if (NAMESPACE_SEPARATOR === $revisedClass)
      $trimmedClass = $revisedClass;
    elseif ($trimmedClass === 'DevControllerTrait' || $trimmedClass === 'ProdControllerTrait')
    {
      echo CLI_INFO, ($trimmedClass === 'DevControllerTrait'
        ? 'We will not send the development controller in production.'
        : 'ProdControllerTrait has been already loaded by a require statement.'),
        END_COLOR, PHP_EOL;

      return;
    } else
    {
      $cacheNamespace = 'cache\\php';

      // Handles cache/php namespaces and otra namespaces
      if (!str_starts_with($trimmedClass, $cacheNamespace))
      {
        // It could, for example, be a SwiftMailer class
        if (VERBOSE > 0 && !isset(SPECIAL_CLASSES[$chunk]) && str_contains($trimmedClass, 'vendor') && !str_contains($chunk, '//'))
          echo CLI_INFO_HIGHLIGHT, 'EXTERNAL LIBRARY CLASS : ' . $chunk, END_COLOR, PHP_EOL;
      } elseif ($trimmedClass === $cacheNamespace . '\\BlocksSystem')
        // The class cache\php\BlocksSystem is already loaded via the MasterController class
        return;
    }
  }

  // must be a native class like Exception to not be in the class map
  if (!isset(CLASSMAP[$trimmedClass]))
    return;

  $tempFile = CLASSMAP[$trimmedClass];

  // We add the file found in the use statement only if we don't have it yet
  if (!in_array($tempFile, $parsedFiles))
  {
    if (!isset($filesToConcat['php']))
    {
      $filesToConcat['php'] = [
        'use' => [],
        OTRA_KEY_REQUIRE => [],
        OTRA_KEY_EXTENDS => [],
        OTRA_KEY_STATIC => []
      ];
    }

    $parsedClasses[] = $lastClassSegment;
    $filesToConcat['php']['use'][] = $tempFile;
    $parsedFiles[] = $tempFile;
  } elseif (VERBOSE > 1)
    showFile($level, $tempFile, ' via use statement' . OTRA_ALREADY_PARSED_LABEL);
}
