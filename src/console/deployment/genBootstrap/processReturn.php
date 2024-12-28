<?php
declare(strict_types=1);
namespace otra\console\deployment\genBootstrap;
/**
 * Process the return inside the PHP content from $contentToAdd and include it directly in the $finalContent.
 *
 * @param string $includingCode    The whole file that contains the $inclusionCode
 * @param string $includedCode     Like "<?php declare(strict_types=1);return [...];?>"
 * @param string $inclusionCode    Like "'require BUNDLES_PATH . 'config/Routes.php');"
 * @param int    $inclusionCodePos $inclusionCode position in $includingCode
 */
function processReturn(string &$includingCode, string &$includedCode, string $inclusionCode, int $inclusionCodePos) : string
{
  // We change only the `require` but we keep the parenthesis and the semicolon
  // (cf. BASE_PATH . config/Routes.php init function)
  preg_match(
    '@^\s*(?>require|include)(?>_once)?\s(?>([^)]))+@',
    $inclusionCode,
    $matches,
    PREG_OFFSET_CAPTURE
  );
  /* @var array<int, array{string, int}> $matches */

  // Removes `;` only if necessary, that is, if it was in a function
  if (!str_contains(substr($includingCode, $inclusionCodePos + $matches[1][1] - 4, 6), ';'))
    $includedCode = rtrim($includedCode, ';');

  $includingCode = substr_replace($includingCode, $includedCode, $inclusionCodePos, $matches[1][1]);
  return trim($matches[0][0]);
}
