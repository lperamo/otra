<?php
declare(strict_types=1);
namespace otra\console\deployment\genBootstrap;

if (!function_exists(__NAMESPACE__ . '\\startsWithPhp'))
{
  define(__NAMESPACE__ . '\\CLASS_TOKENS', [
    T_CLASS => true,
    T_INTERFACE => true,
    T_TRAIT => true,
    T_ENUM => true
  ]);
  define(__NAMESPACE__ . '\\DECLARE_PATTERN', '@\s*declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;@m');
  define(__NAMESPACE__ . '\\PHP_OPEN_TAG_SHORT_STRING', '<?=');
  define(__NAMESPACE__ . '\\TYPE_VALUE_HTML', 'html');
  define(__NAMESPACE__ . '\\TYPE_VALUE_PHP', 'php');

  function startsWithPhp(string $content): bool
  {
    $trimmedContent = trim($content);

    return strncmp($trimmedContent, PHP_OPEN_TAG_STRING, PHP_OPEN_TAG_LENGTH) === 0
      || strncmp($trimmedContent, PHP_OPEN_TAG_SHORT_STRING, 3) === 0;
  }

  /**
   * @param string $content
   * @param array  $parsedClasses
   *
   * @return array{
   *   0: array<string,string>,
   *   1: string[],
   *   2: string[],
   *   3: array<string,string>
   * } [$parts, $useConst, $useFunction, $globalConstants]
   */
  function separatePhpAndHtml(string $content, array &$parsedClasses): array
  {
    $useConst = $useFunction = $globalConstants = $parts = [];
    $isPhp = startsWithPhp($content);
    $phpPattern = '/(<\?php|<\?=).*?(\?>|\z)/s';

    if (preg_match_all($phpPattern, $content, $matches, PREG_OFFSET_CAPTURE))
    {
      $lastEnd = 0;

      foreach ($matches[0] as $matchKey => $match)
      {
        $start = $match[1];

        if ($start > $lastEnd)
        {
          $parts[] = [
            'type' => TYPE_VALUE_HTML,
            'content' => substr($content, $lastEnd, $start - $lastEnd)
          ];
        }

        $phpContent = (array_key_last($matches[0]) === $matchKey
          ? $match[0]
          : str_replace(PHP_END_TAG_STRING, '', rtrim($match[0]))
        );

        // We remove the PHP end tag if this is the last part
        [$newParts, $useConst, $useFunction, $globalConstants] = separateInsideAndOutsidePhp($phpContent, $parsedClasses);

        $parts[] = [
          'type' => TYPE_VALUE_PHP,
          'content' => $newParts
        ];

        $lastEnd = $start + strlen($match[0]);
      }

      if ($lastEnd < strlen($content))
      {
        $parts[] = [
          'type' => TYPE_VALUE_HTML,
          'content' =>  str_replace(PHP_END_TAG_STRING, '', substr($content, $lastEnd))
        ];
      }
    } elseif ($isPhp)
    {
      [$newParts, $useConst, $useFunction, $globalConstants] = separateInsideAndOutsidePhp(
        str_replace(PHP_END_TAG_STRING, '', rtrim($content)),
        $parsedClasses
      );
      $parts[] = [
        'type' => TYPE_VALUE_PHP,
        'content' => $newParts
      ];
    } else
    {
      $parts[] = [
        'type' => TYPE_VALUE_HTML,
        'content' => str_replace(PHP_END_TAG_STRING, '', rtrim($content))
      ];
    }

    return [$parts, $useConst, $useFunction, $globalConstants];
  }

  /**
   * @param string $nextContentToAdd File content that will be used for the next iteration.
   *
   * @return array{
   *    0: string,
   *    1: string[], 
   *    2: string[], 
   *    3: string[], 
   *    4: array<string, string> 
   *  } [$insideContent, $contentPartsOutside,$useConst, $useFunction, $globalConstants]
   */
  function handlePhpAndHtml(string &$nextContentToAdd, array &$parsedClasses): array
  {
    [$contentParts, $useConst, $useFunction, $globalConstants] = separatePhpAndHtml($nextContentToAdd, $parsedClasses);
    
    $contentPartsInsideTemp = $contentPartsOutside = [];
    $insideContent = '';

    foreach ($contentParts as $contentPart)
    {
      $contentPartIsPhp = $contentPart['type'] === TYPE_VALUE_PHP
        || $contentPart['type'] === 'phpOutside'
        || $contentPart['type'] === 'phpInside';

      if ($contentPartIsPhp)
      {
        $contentPartTemp = '';

        foreach ($contentPart['content'] as $contentPartItem)
        {
          if (str_contains($contentPartItem['content'], 'class')
            || str_contains($contentPartItem['content'], 'namespace')
            || str_contains($contentPartItem['content'], 'const')
            || str_contains($contentPartItem['content'], 'function'))
          {
            $contentPartItem['content'] = preg_replace(
              [
                '@^\s*namespace\s+[a-zA-Z0-9\\\\]+\s*;                  # inline namespace
      |^\s*namespace\s+[a-zA-Z0-9\\\\]+\s*\{([^{}]+|\{[^{}]*})*}@mx', // block namespace
                DECLARE_PATTERN
              ],
              '',
              $contentPartItem['content']
            );
          }

          if ($contentPartItem['type'] === 'phpInside')
            $contentPartTemp .= $contentPartItem['content'];
          else
            $contentPartsOutside[] = $contentPartItem['content'];
        }
      } else
        $contentPartTemp = $contentPart['content'];

      $insideContentToAdd = rtrim($contentPartTemp, PHP_EOL);

      // We store information about whether the content is PHP or HTML
      $contentPartsInsideTemp[]= [
        'php' => $contentPartIsPhp,
        'content' => $insideContentToAdd
      ];
    }

    $lastValueType = true;

    foreach($contentPartsInsideTemp as $contentIndex => $contentPartInsideTemp)
    {
      $contentPartInsideTemp['content'] = rtrim($contentPartInsideTemp['content']);
      $firstBlock = array_key_first($contentPartsInsideTemp) === $contentIndex;
      $wasPhp = $firstBlock || $lastValueType;

      // *** specific rules for the first block ***
      if ($firstBlock && $contentPartInsideTemp['php'])
      {
        // The first content is PHP? We do not need to start the PHP tag as we already begin with PHP
        if (str_starts_with($contentPartInsideTemp['content'], PHP_OPEN_TAG_STRING))
          $contentPartInsideTemp['content'] = substr(
            $contentPartInsideTemp['content'],
            PHP_OPEN_TAG_LENGTH
          );
        // The first content is PHP? We do not need to end the PHP tag as we already begin with PHP
        if (str_starts_with($contentPartInsideTemp['content'], PHP_END_TAG_STRING))
          $contentPartInsideTemp['content'] = substr($contentPartInsideTemp['content'], PHP_END_TAG_LENGTH);
      }

      // if the last content was PHP, and we have PHP
      if ($wasPhp)
      {
        if ($contentPartInsideTemp['php'])
          $insideContent .= $contentPartInsideTemp['content'];
        // if the last content was PHP, and we have HTML
        elseif (!str_ends_with($insideContent, PHP_END_TAG_STRING))
          $insideContent .= PHP_END_TAG_STRING . $contentPartInsideTemp['content'];
      }
      // if the last content was HTML, and we have PHP
      elseif ($contentPartInsideTemp['php']
        && !str_starts_with($contentPartInsideTemp['content'], PHP_OPEN_TAG_SHORT_STRING))
        $insideContent .= PHP_OPEN_TAG_STRING . ' ' . $contentPartInsideTemp['content'];

      // if the last content was HTML, and we have HTML,
      // or it does not match any other conditions...
      else
        $insideContent .= $contentPartInsideTemp['content'];

      if (!$contentPartInsideTemp['php'] && array_key_last($contentPartsInsideTemp) === $contentIndex)
        $insideContent .= PHP_OPEN_TAG_STRING . ' ';

      $lastValueType = $contentPartInsideTemp['php'];
    }

    // if we have PHP end tag at the end, and we have PHP code, we remove it
    if (str_ends_with($insideContent, PHP_END_TAG_STRING))
      $insideContent = substr($insideContent, 0, -PHP_END_TAG_LENGTH);

    return [
      $insideContent,
      $contentPartsOutside,
      $useConst,
      $useFunction,
      $globalConstants
    ];
  }
}

