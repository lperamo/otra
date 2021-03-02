<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\tools
 */

if (!function_exists('t'))
{
  // Will be the future translation feature
  function t(string $text): string
  {
    return $text;
  }

  /**
   * @param array $acceptedLanguages
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

