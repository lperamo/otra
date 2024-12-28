<?php
declare(strict_types=1);
namespace otra\console\deployment\genBootstrap;

use const otra\cache\php\CLASSMAP;
use const otra\console\{CLI_INFO, CLI_WARNING, END_COLOR};

/**
 * Returns false if :
 *
 * - We found this class in already parsed classes
 * - There is no namespace
 * - The class is not part of the generated classmap
 *
 * @param array  $classesFromFile Already parsed classes
 * @param string $class           Class to analyze/search
 * @param int    $matchOffset     Content extract position where the class was found
 *
 * @return false|string $tempFile
 */
function searchForClass(array $classesFromFile, string $class, string $contentToAdd, int $matchOffset) : false|string
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
  $contentToAddLength = mb_strlen($contentToAdd);
  $namespacePosition = mb_strrpos($contentToAdd, 'namespace', $matchOffset - $contentToAddLength);

  // no namespace found, we return false
  if ($namespacePosition === false)
    return false;

  // then we find the section that interests us
  $tempContent = mb_substr($contentToAdd, $namespacePosition, $matchOffset - $namespacePosition);

  // then we can easily extract the namespace (10 = strlen('namespace'))
  // and concatenates it with a '\' and the class to get our file name
  $newClass = mb_substr(
      $tempContent,
      10,
      mb_strpos($tempContent, ';') - 10
    ) . NAMESPACE_SEPARATOR . $class;

  if (!isset(CLASSMAP[$newClass]))
  {
    // Check if the class usage is in a comment
    $classPosition = mb_strpos($contentToAdd, $class);
    $lineStart = mb_strrpos($contentToAdd, "\n", $classPosition - $contentToAddLength) ?: 0;
    $trimmedCodeLine = trim(
      mb_substr(
        $contentToAdd,
        $lineStart,
        (mb_strpos($contentToAdd, "\n", $classPosition) ?: $contentToAddLength - $lineStart) - $lineStart
      )
    );

    if (VERBOSE > 0 && !preg_match('@^\s*(?://|/\*|\*)@', $trimmedCodeLine))
    {
      echo CLI_WARNING, 'Notice : Please check if you use a class ', CLI_INFO, $class, CLI_WARNING,
        ' in a use statement. This file seems to be not included! Found here:', PHP_EOL,
        CLI_INFO, $trimmedCodeLine, END_COLOR, PHP_EOL;
    }

    return false;
  }

  return CLASSMAP[$newClass];
}
