<?php
declare(strict_types=1);
namespace otra\console\deployment\genBootstrap;

const
  NAMESPACE_PATTERN = '@\s*namespace\s+[a-zA-Z0-9\\\\]+\s*;        # inline namespace
    |\s*namespace\s+[a-zA-Z0-9\\\\]+\s*\{([^{}]+|\{[^{}]*})*}@mx',
  USE_PATTERN = '@(?<!//|/\*)\s*\buse\s+[^();]+(?!Trait);\s*@m',
  USE_CONST_PATTERN = '@(?<!//|/\*)\s*\buse\s*const\s+[\w\s,]+;\s*@m',
  USE_FUNCTION_PATTERN = '@(?<!//|/\*)\s*\buse\s*function\s+[\w\s,\\\\]+;\s*@m',
  MODIFIER_TOKENS = [
    T_ABSTRACT => true,
    T_FINAL => true,
    T_READONLY => true
  ];

/**
 * @param string $code
 *
 * @return array<0: string, 1: string[]> $globalConstants, $fullDeclarationsBlock
 */
function extractConstants(string $code) : array
{
  $tokens = token_get_all($code);
  $actualFullDeclarationBlock = '';
  $constDeclarationStart = $hasToStoreConstant = false;
  $globalConstants = $fullDeclarationsBlock = [];
  $constKey = true;
  $previousKey = '';
  $bracketsDepth = $parenthesesDepth = 0;
  $finalConst = 'const ';
  $constantValueStart = false;

  foreach ($tokens as $token)
  {
    if (is_array($token))
    {
      $tokenValue = $token[1];

      if ($token[0] === T_CONST)
        $constDeclarationStart = true;
      elseif (
        $token[0] !== T_WHITESPACE &&
        $tokenValue !== PHP_END_TAG_STRING &&
        $constDeclarationStart)
      {
        if ($constKey)
        {
          $constKey = false;

          if (isset($globalConstants[$previousKey]) && str_ends_with($globalConstants[$previousKey], ','))
            $globalConstants[$previousKey] = substr($globalConstants[$previousKey], 0, -1);

          if (!isset($globalConstants[$tokenValue]))
          {
            $previousKey = $tokenValue;
            $finalConst .= $tokenValue;
            $hasToStoreConstant = true;
            $globalConstants[$tokenValue] = '';
          } else
            $hasToStoreConstant = false;
        } elseif ($hasToStoreConstant)
        {
          $constantValueStart = true;
          $finalConst .= $tokenValue;
//          var_dump($previousKey, $tokenValue);

          $globalConstants[$previousKey] .= $tokenValue;
        }
      }
    } elseif (
      ($token === '=' ||
        $token === '.' ||
        $token === ',' ||
        $token === '[' ||
        $token === ']' ||
        $token === '(' ||
        $token === ')' ||
        $token === '+' ||
        $token === '/' ||
        $token === '*' ||
        $token === '-' ||
        $token === '^' ||
        $token === '|' ||
        $token === '&' ||
        $token === '?' ||
        $token === ':'
      )
      && $hasToStoreConstant
      && $constDeclarationStart)
    {
      $finalConst .= $token;

      if ($token !== '=')
      {
        if ($token !== ',' || $constantValueStart)
          $globalConstants[$previousKey] .= $token;

        $constKey = ($token === ',' && $parenthesesDepth === 0 && $bracketsDepth === 0);
      } else
        $constKey = false;

      if ($token === '(')
        ++$parenthesesDepth;
      elseif ($token === ')')
        --$parenthesesDepth;
      elseif ($token === '[')
        ++$bracketsDepth;
      elseif ($token === ']')
        --$bracketsDepth;
    } elseif($constDeclarationStart)
    {
      if ($token === '(')
        ++$parenthesesDepth;
      elseif ($token === ')')
        --$parenthesesDepth;
      elseif ($token === '[')
        ++$bracketsDepth;
      elseif ($token === ']')
        --$bracketsDepth;
      elseif ($token === ';'
        && $bracketsDepth === 0
        && $parenthesesDepth === 0)
      {
        $constantValueStart = false;

        if (!str_ends_with($finalConst, ',') && $hasToStoreConstant)
          $finalConst .= ',';

        $actualFullDeclarationBlock .= $token;
        $fullDeclarationsBlock[]= $actualFullDeclarationBlock;
        $constKey = true;
        $constDeclarationStart = false;
        $actualFullDeclarationBlock = '';
      }
    }

    if ($constDeclarationStart)
      $actualFullDeclarationBlock .= is_array($token) ? $token[1] : $token;
  }

  return [$globalConstants, $fullDeclarationsBlock];
}

function removeCommentsButAnnotations(string $code): string
{
  $tokens = token_get_all($code);
  $result = '';
  $lastTokenWasComment = false;
  $lastNonWhitespaceToken = null;

  foreach ($tokens as $token)
  {
    if (!is_array($token))
    {
      $result .= $token;
      $lastTokenWasComment = false;
      $lastNonWhitespaceToken = null;
      continue;
    }

    switch ($token[0])
    {
      case T_DOC_COMMENT: // matches `/*`
        if (str_contains($token[1], '@'))
        {
          if ($lastTokenWasComment)
            $result = rtrim($result) . ' ';

          $result .= trim($token[1]);
          $lastTokenWasComment = true;
        }
        break;

      case T_COMMENT: // matches `//`
        // Do nothing, ignore the non DocBlock comments
        $lastTokenWasComment = true;
        break;

      case T_WHITESPACE: // matches ` `
        if (!$lastTokenWasComment && $lastNonWhitespaceToken !== T_COMMENT)
          $result .= $token[1];

        break;

      default:
        $result .= $token[1];
        $lastTokenWasComment = false;
        $lastNonWhitespaceToken = $token[0];
        break;
    }
  }

  return $result;
}

function makeReplacement(string $searchPattern, string $subject): string
{
  return preg_replace(
    '/' . preg_quote($searchPattern, '/') . '/',
    '',
    $subject,
    1
  );
}

function extractBlock(string $pattern, string &$insideContent, string &$variable) : void
{
  if (preg_match_all($pattern, $insideContent, $matches, PREG_SET_ORDER))
  {
    foreach ($matches as $match)
    {
      $variable .= $match[0];
      $insideContent = makeReplacement($match[0], $insideContent);
    }
  }
}

/**
 * @param string   $phpContent
 * @param string[] $parsedClasses
 *
 * @return array{
 *    0: array<string,string>,
 *    1: string[],
 *    2: string[],
 *    3: array<string,string>
 *  } [$parts, $useConst, $useFunction, $globalConstants]
 */
function separateInsideAndOutsidePhp(string $phpContent, array &$parsedClasses): array
{
  // Remove comments
  $phpContent = removeCommentsButAnnotations($phpContent);
  $useConst = $useFunction = $outsideContent = '';
  $insideContent = $phpContent;

  extractBlock(USE_CONST_PATTERN, $insideContent, $useConst);
  extractBlock(USE_FUNCTION_PATTERN, $insideContent, $useFunction);

  if (preg_match_all(USE_PATTERN, $insideContent, $matches, PREG_SET_ORDER))
  {
    foreach ($matches as $match)
    {
      if (str_contains($match[0], 'Trait'))
        continue;

      $insideContent = makeReplacement($match[0], $insideContent);
    }
  }

  // Extract classes
  $tokens = token_get_all($insideContent);
  $classContent = $newInsideContent = '';
  $inClass = false;
  $braceLevel = 0;

  for ($tokenIndex = 0, $tokensLength = count($tokens); $tokenIndex < $tokensLength; ++$tokenIndex)
  {
    $token = $tokens[$tokenIndex];

    if (is_array($token))
    {
      $tokenType = $token[0];

      if (isset(CLASS_TOKENS[$tokenType]) || isset(MODIFIER_TOKENS[$tokenType]))
      {
        if (!$inClass)
        {
          $inClass = true;
          $classContent = '';

          // Retrieve the class name
          for ($classNameTokenIndex = $tokenIndex + 1; $classNameTokenIndex < $tokensLength; $classNameTokenIndex++)
          {
            if ($tokens[$classNameTokenIndex][0] === T_STRING)
            {
              $parsedClasses[] = $tokens[$classNameTokenIndex][1];
              break;
            }
          }
        }

        $classContent .= $token[1];
      }
      elseif ($inClass)
        $classContent .= $token[1];
      else
        $newInsideContent .= $token[1];
    } elseif ($inClass)
    {
      $classContent .= $token;

      if ($token === '{')
        ++$braceLevel;
      elseif ($token === '}')
      {
        if (--$braceLevel === 0)
        {
          $outsideContent .= $classContent . PHP_EOL;
          $inClass = false;
        }
      }
    } else
      $newInsideContent .= $token;
  }

  $insideContent = trim($newInsideContent);

  // Extract functions using token_get_all
  $tokens = token_get_all($insideContent);
  $braceLevel = $conditionalLevel = 0;
  $buffer = $conditionalBuffer = '';
  $functionStarted = $isConditionalFunction = false;

  foreach ($tokens as $token)
  {
    if (is_array($token))
    {
      [$identifier, $textFromInsideContent] = $token;

      if ($identifier === T_IF || $identifier === T_ELSEIF || $identifier === T_ELSE)
      {
        ++$conditionalLevel;
        $conditionalBuffer .= $textFromInsideContent;
      } elseif ($identifier === T_FUNCTION && $braceLevel === 0)
      {
        $functionStarted = true;
        $isConditionalFunction = ($conditionalLevel > 0);

        // Check if previous token is a variable (indicating an anonymous function)
        if (isset($previousToken)) 
          $buffer = $previousToken;
        elseif ($conditionalLevel > 0)
          $buffer .= $conditionalBuffer;
      }

      // Check if we are starting an anonymous function affected to a variable
      if ($token[0] === T_VARIABLE && !$functionStarted)
        $previousToken = $token[1];
      elseif (isset($previousToken))
        $previousToken .= $token[1];

      if ($functionStarted || $conditionalLevel > 0)
        $buffer .= $textFromInsideContent;
    } else
    {
      if ($functionStarted || $conditionalLevel > 0)
        $buffer .= $token;

      if ($token === '{')
        ++$braceLevel;
      elseif ($token === '}')
      {
        --$braceLevel;

        if ($braceLevel === 0)
        {
          if ($functionStarted)
          {
            if (!$isConditionalFunction && !isset($previousToken))
            {
              $outsideContent .= $buffer;
              $insideContent = str_replace($buffer, '', $insideContent);
            }

            $buffer = '';
            $functionStarted = $isConditionalFunction = false;
          }

          $conditionalLevel = 0;
          $conditionalBuffer = '';
        }
      } elseif (isset($previousToken))
        $previousToken .= $token;
    }
  }

  // Handle the case where there is remaining content in the buffer
  if (!empty($buffer))
    $insideContent = str_replace($buffer, $buffer, $insideContent);

  // Retrieve constants
  [$globalConstants, $fullDeclarationsBlock] = extractConstants($insideContent);
//  var_dump($fullDeclarationsBlock);

  // remove constants block as we have processed them
  foreach ($fullDeclarationsBlock as $fullDeclarationBlock)
    $insideContent = str_replace($fullDeclarationBlock, '', $insideContent);

  // Extract namespaces
  if (preg_match_all(NAMESPACE_PATTERN, $insideContent, $matches, PREG_SET_ORDER))
  {
    foreach ($matches as $match)
    {
      // for now, we just remove the namespace and just keep its content
      $outsideContent .= preg_replace(
        '@^\s*namespace\s+[a-zA-Z0-9\\\\]+\s*;
        |^\s*namespace\s+[a-zA-Z0-9\\\\]+\s*\{(.*?)}(?:\s*$|\s*(?=namespace))@sx',
        '$1',
        $match[0] ?? ''
      );
      $insideContent = makeReplacement($match[0], $insideContent);
    }
  }

  // Remove the strict_types declarations
  $insideContent = preg_replace(DECLARE_PATTERN, ' ', $insideContent);
  $parts = [];
  $trimmedOutsideContent = trim($outsideContent);

  if ($trimmedOutsideContent !== '')
  {
    $parts[] = [
      'type' => 'phpOutside',
      'content' => $trimmedOutsideContent
    ];
  }

  $trimmedInsideContent = trim($insideContent);

  if ($trimmedInsideContent !== '')
  {
    if (str_starts_with($trimmedInsideContent, PHP_OPEN_TAG_STRING))
    {
      $trimmedInsideContent = substr(
        $trimmedInsideContent,
        $trimmedInsideContent[PHP_OPEN_TAG_LENGTH] === ' '
          ? PHP_OPEN_TAG_AND_SPACE_LENGTH
          : PHP_OPEN_TAG_LENGTH
      );
    }

    if (str_ends_with($trimmedInsideContent, PHP_END_TAG_STRING))
      $trimmedInsideContent = substr($trimmedInsideContent, 0, -PHP_END_TAG_LENGTH);

    $trimmedInsideContent = trim($trimmedInsideContent);

    if ($trimmedInsideContent !== '')
    {
      $parts[] = [
        'type' => 'phpInside',
        'content' => $trimmedInsideContent
      ];
    }
  }

  return [$parts, $useConst, $useFunction, $globalConstants];
}
