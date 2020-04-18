<?php
if (function_exists('t') === false)
{
  // Will be the future translation feature
  function t(string $text): string
  {
    return $text;
  }
}
