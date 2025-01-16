<?php
declare(strict_types=1);
namespace otra\console\deployment\genBootstrap;

const RESOLVE_CONST_PATTERN = '@^[a-zA-Z_][a-zA-Z0-9_]*(\\\\[a-zA-Z_][a-zA-Z0-9_]*)*$@';
/**
 * @param string $constant
 *
 * @return string
 */
 function resolveConstant(string $constant) : string
 {
  if (defined($constant)) 
  {
    $value = constant($constant);

    if (preg_match(RESOLVE_CONST_PATTERN, $value))
      return resolveConstant($value);

    return $value;
  }

  return $constant;
}

/**
 * Resolves an expression into an inclusion path string.
 *
 * This function processes PHP expressions used for including files by simplifying
 * and resolving constants, functions, and string literals.
 *
 * @param string $expression The PHP expression to resolve, such as 'BASE_PATH . "/dir/file.php"'.
 *
 * @return string The resolved path ready to be used in an inclusion, with dynamic parts
 *                (like variables or unresolved function calls) left intact.
 */
function resolveInclusionPath(string $expression): string
{
  // If the expression contains a PHP variable, return it as is because it cannot be statically resolved.
  if (str_contains($expression, '$'))
  {
    preg_match_all('@\\$([^\\s)(]+)@', $expression, $pathVariables, PREG_SET_ORDER);

    foreach($pathVariables as $pathVariable)
    {
      $aPathVariable = $pathVariable[1];

      if (isset(PATH_CONSTANTS[$aPathVariable]))
      {
        $expression = str_replace(
          '$' . $aPathVariable,
          '\'' . PATH_CONSTANTS[$aPathVariable] . '\'',
          $expression
        );
      }
    }

    if (str_contains($expression, '$'))
      return $expression;
  }

  // Split the expression into parts using concatenation, quotes, and spaces as delimiters.
  $requireParts = preg_split('/\s*[\'" ]\.[\'" ]\s*/', $expression);
  $result = '';

  foreach ($requireParts as $requirePart)
  {
    $requirePart = trim($requirePart);

    if (preg_match('/^([\'"])((?:(?!\1).)*)\1$/', $requirePart, $matches))
    {
      // If the part is a string literal (enclosed in quotes), add it without the quotes
      $result .= $matches[2];
    }
    elseif (preg_match(RESOLVE_CONST_PATTERN, $requirePart))
    {
      $result .= (defined($requirePart))
        // If the part is a defined constant, replace it with its value
        ? resolveConstant($requirePart)
        : $requirePart . '.\'';
    }
    elseif (preg_match('/^(\w+)\(([^)]+)\)$/', $requirePart, $matches))
    {
      // If the part is a function call (like ucFirst), execute the function if it exists and add its result
      $function = $matches[1];
      $result .= function_exists($function)
        ? $function(trim($matches[2], '\'"'))
        : $requirePart; // If the function doesn't exist, keep the expression as is
    }
    else
      // If the part is neither a string, nor a constant, nor a function call,
      // treat it as a raw string and keep it as is
      $result .= preg_replace("/^.*?'([^']+)'.*$/", '$1', $requirePart);
  }

  if (substr_count($result, "'") % 2 === 1)
    $result .= "'";

  // Simplify redundant concatenations `' . '` and remove trailing semicolons.
  return str_replace(
    '\' . \'',
    '',
    rtrim(trim($result), ';')
  );
}
