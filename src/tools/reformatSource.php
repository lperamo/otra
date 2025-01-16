<?php
declare(strict_types=1);
namespace otra\tools;
/**
 * @author Lionel Péramo
 * @package otra\tools
 */

/**
 * Puts <br> between markups to add legibility to a code in debug mode and convert other markups in HTML
 * entities.
 *
 * @param string $stringToFormat The ... (e.g.: self::$template)
 *
 * @return string The formatted string
 */
function reformatSource(string $stringToFormat) : string
{
  return preg_replace('@&gt;\s*&lt;@', "&gt;<br/>&lt;", htmlspecialchars($stringToFormat));
}
