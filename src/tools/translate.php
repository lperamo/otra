<?php
declare(strict_types=1);
namespace otra\tools;
/**
 * @author Lionel Péramo
 * @package otra\tools
 */

if (!function_exists('otra\tools\t'))
{
  // Will be the future translation feature
  function trans(string $text): string
  {
    return $text;
  }

  /**
   * @param string[] $acceptedLanguages
   *
   * @return string
   */
  function getPreferredLanguage(array $acceptedLanguages) : string
  {
    // It seems to bug when I use "function setLang" instead of "$setLang = function" with the array_filter.
    $setLang = function (string &$language) : void
    {
      $language = strtok($language, ';');
    };

    $filterLangs = function (string $lang) use ($acceptedLanguages) : bool
    {
      return in_array($lang, $acceptedLanguages);
    };

    $languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    array_walk($languages, $setLang);

    return array_filter($languages, $filterLangs)[0] ?: 'en';
  }
}
