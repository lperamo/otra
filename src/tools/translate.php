<?php
declare(strict_types=1);
if (function_exists('t') === false)
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
    $setLang = function (string &$lang) : void
    {
      $lang = strtok($lang, ';');
    };

    $filterLangs = function (string &$lang) use ($acceptedLanguages) : bool
    {
      return in_array($lang, $acceptedLanguages);
    };

    $langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    array_walk($langs, $setLang);

    return array_filter($langs, $filterLangs)[0] ?: 'en';
  }
}

