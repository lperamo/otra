<?php
declare(strict_types=1);

use JetBrains\PhpStorm\Pure;

/**
 * @author Lionel Péramo
 * @package otra\tools\files
 */

/**
 * Returns BASE_PATH the/path with BASE_PATH in light blue whether the resource is contained in the BASE_PATH
 * otherwise returns resource name as is.
 *
 * @param string      $resource Most of the time the name of a folder
 * @param string|null $fileName Most of the time the name of a file
 * @param bool|null   $endColor Do we have to reset color at the end ?
 *
 * @return string
 */
#[Pure] function returnLegiblePath(string $resource, ?string $fileName = '', ?bool $endColor = true) : string
{
  // Avoid to finish with '/' if $resource is not a folder (and then $fileName = '')
  if ($fileName !== '')
    $fileName = '/' . $fileName;

  return (str_contains($resource, BASE_PATH)
      ? CLI_LIGHT_BLUE . 'BASE_PATH ' . CLI_LIGHT_CYAN . substr($resource, strlen(BASE_PATH)) . $fileName . END_COLOR
      : CLI_LIGHT_CYAN . $resource . $fileName . END_COLOR)
    . ($endColor ? END_COLOR : '');
}
