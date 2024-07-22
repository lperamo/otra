<?php
declare(strict_types=1);
namespace otra\console\deployment\genBootstrap;
use JetBrains\PhpStorm\ArrayShape;
use const otra\console\{CLI_INFO_HIGHLIGHT, CLI_WARNING, END_COLOR};

/**
 * We test if we have dynamic variables into the require/include statement, and replace them if they exists
 *
 * @param string $fileContent  Content of the file $filename
 * @param string $trimmedMatch Match (with spaces neither to the left nor to the right) in the file $filename that
 *                             potentially contains dynamic variable to change
 *
 * @return array{0: string, 1: bool} [$fileContent, $isTemplate]
 */
#[ArrayShape([
  'string',
  'bool',
  'bool'
])]
function evalPathVariables(string &$fileContent, string $filename, string $trimmedMatch) : array
{
  $cannotIncludeFile = false;

  // no path variables found
  if (!str_contains($trimmedMatch, '$'))
    return [$fileContent, (str_contains($trimmedMatch, '.phtml')), false];

  preg_match_all('@\\$([^\\s)(]+)@', $fileContent, $pathVariables, PREG_SET_ORDER);
  $isTemplate = false;

  if (!empty($pathVariables))
  {
    foreach($pathVariables as $pathVariable)
    {
      $aPathVariable = $pathVariable[1];

      if (isset(PATH_CONSTANTS[$aPathVariable]) && str_contains($trimmedMatch, $aPathVariable))
      {
        $fileContent = str_replace(
          '$' . $aPathVariable,
          '\'' . PATH_CONSTANTS[$aPathVariable] . '\'',
          $fileContent
        );
      }
      /* we make an exception for this particular require statement because
         it is a `require` made by the prod controller, and then it is a template ...(so no need to include it, for now)
       */
      elseif ('templateFilename' === trim($aPathVariable) || str_contains(trim($aPathVariable), '.phtml'))
        $cannotIncludeFile = $isTemplate = true;
      elseif ('require_once CACHE_PATH . \'php/\' . $route . \'.php\';' !== $trimmedMatch
        && 'require $routeSecurityFilePath;' !== $trimmedMatch
        && 'require $renderController->viewPath . \'renderedWithoutController.phtml\';' !== $trimmedMatch
        && 'require self::$sessionFile;' !== $trimmedMatch
      )
      {
        echo CLI_WARNING, 'require/include statement not evaluated because of the non defined dynamic variable ',
        CLI_INFO_HIGHLIGHT, '$', $aPathVariable, CLI_WARNING, ' in', PHP_EOL,
        '  ', CLI_INFO_HIGHLIGHT, $trimmedMatch, CLI_WARNING, PHP_EOL,
        '  in the file ', CLI_INFO_HIGHLIGHT, $filename, CLI_WARNING, '!', END_COLOR, PHP_EOL;
        $cannotIncludeFile = true;
      }
    }
  }

  return [$fileContent, $isTemplate, $cannotIncludeFile];
}
