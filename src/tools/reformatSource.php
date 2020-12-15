<?php
/**
 * Puts <br> between markups in order to add legibility to a code in debug mode and convert other markups in html
 * entities.
 *
 * @param string $stringToFormat The ... (e.g. : self::$template)
 *
 * @return string The formatted string
 */
function reformatSource(string $stringToFormat) : string
{
  return preg_replace('@&gt;\s*&lt;@', "&gt;<br/>&lt;", htmlspecialchars($stringToFormat));
}
