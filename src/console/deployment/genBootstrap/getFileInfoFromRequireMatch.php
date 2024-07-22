<?php
declare(strict_types=1);
namespace otra\console\deployment\genBootstrap;
use JetBrains\PhpStorm\ArrayShape;
use otra\OtraException;
use const otra\cache\php\CONSOLE_PATH;

/**
 * Extracts the file name and the 'isTemplate' information from the require/include statement.
 * Potentially replaces dynamic $variables by their value to know which file to use.
 *
 * @param string $trimmedMatch Match (with spaces neither to the left nor to the right) in the file $filename that
 *                             potentially contains dynamic variable to change
 *
 * @throws OtraException
 * @return array{0: string, 1: bool} [$tempFile, $isTemplate]
 */
#[ArrayShape([
  'string',
  'bool',
  'bool'
])]
function getFileInfoFromRequireMatch(string $trimmedMatch, string $filename) : array
{
  // Extracts the file name in the require/include statement ...
  preg_match('@(?:require|include)(?:_once)?\s+([^;]+)\\)?@m', $trimmedMatch, $inclusionMatches);
  $fileContent = $inclusionMatches[1];

  /* Not sure of the safety of this code
     We check if the require/include statement is in a function call
     by counting the number of parenthesis between the require statement and the semicolon */
  if (0 !== (mb_substr_count($trimmedMatch, ')') + mb_substr_count($trimmedMatch, '(')) % 2 )
    $fileContent = mb_substr($fileContent, 0, -1);

  require_once CONSOLE_PATH . 'deployment/genBootstrap/evalPathVariables.php';
  return evalPathVariables($fileContent, $filename, $trimmedMatch);
}
