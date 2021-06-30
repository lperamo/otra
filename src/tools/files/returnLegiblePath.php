<?php
/**
 * @author  Lionel Péramo
 * @package otra\tools\files
 */
declare(strict_types=1);

namespace otra\tools\files;
use const otra\cache\php\{BASE_PATH, DIR_SEPARATOR};
use const otra\console\{CLI_INFO, CLI_INFO_HIGHLIGHT, END_COLOR};

const BASE_PATH_REPLACEMENT = CLI_INFO . 'BASE_PATH + ' . CLI_INFO_HIGHLIGHT;

/**
 * Returns the path with BASE_PATH in light blue whether the resource is contained in the BASE_PATH
 * otherwise returns resource name as is.
 *
 * @param string  $resource Most of the time the name of a folder
 * @param ?string $fileName Most of the time the name of a file
 * @param ?bool   $endColor Do we have to reset color at the end ?
 *
 * @return string
 */
function returnLegiblePath(string $resource, ?string $fileName = '', ?bool $endColor = true) : string
{
  // Avoid to finish with '/' if $resource is not a folder (and then $fileName = '')
  if (!empty($fileName))
    $fileName = DIR_SEPARATOR . $fileName;

  return (str_contains($resource, BASE_PATH)
      ? CLI_INFO . 'BASE_PATH ' . CLI_INFO_HIGHLIGHT . substr($resource, strlen(BASE_PATH)) . $fileName .
        END_COLOR
      : CLI_INFO_HIGHLIGHT . $resource . $fileName . END_COLOR) . ($endColor ? END_COLOR : '');
}

/**
 * We know that we _have_ the value of BASE_PATH in the path so we replace it with 'BASE_PATH' in darker blue plus a
 * ' + ' in lighter blue.
 *
 * @param string $path
 *
 * @return string
 */
function returnLegiblePath2(string $path) : string
{
  return str_replace(BASE_PATH, BASE_PATH_REPLACEMENT, $path) . END_COLOR;
}
