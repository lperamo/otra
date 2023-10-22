<?php
/**
 * @author  Lionel Péramo
 * @package otra\tools\files
 */
declare(strict_types=1);

namespace otra\tools\files;
use const otra\cache\php\BASE_PATH;
use const otra\console\{CLI_INFO, CLI_INFO_HIGHLIGHT, END_COLOR};

if (!function_exists(__NAMESPACE__ . '\\returnLegiblePath'))
{
  define(__NAMESPACE__ . '\\BASE_PATH_REPLACEMENT', CLI_INFO . 'BASE_PATH + ' . CLI_INFO_HIGHLIGHT);


  /**
   * We do _not_ know that we have BASE_PATH in the path.
   * Returns the path with BASE_PATH in light blue whether the resource is contained in the BASE_PATH
   * otherwise returns resource name as is.
   *
   * @param ?string $endColor Must contain '' if we do not want to restore default color
   * @return string
   */
  function returnLegiblePath(string $path, ?string $endColor = END_COLOR) : string
  {
    return (str_contains($path, BASE_PATH)
      ? str_replace(BASE_PATH, BASE_PATH_REPLACEMENT, $path)
      : CLI_INFO_HIGHLIGHT . $path)
      . $endColor;
  }

  /**
   * We know that we _have_ the value of BASE_PATH in the path, so we replace it with 'BASE_PATH' in darker blue plus a
   * ' + ' in lighter blue.
   *
   *
   * @return string
   */
  function returnLegiblePath2(string $path) : string
  {
    return str_replace(BASE_PATH, BASE_PATH_REPLACEMENT, $path) . END_COLOR;
  }
}
