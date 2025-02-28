<?php
/**
 * @author  Lionel Péramo
 * @package otra\console
 */
declare(strict_types=1);

namespace otra\console\constants
{
  use const otra\console\ERASE_SEQUENCE;

  /** This sequence moves the cursor up by 1,
   * move the cursor at the very left,
   * clears all characters from the cursor position to the end of the line (including the character at the cursor position)
   */
  const DOUBLE_ERASE_SEQUENCE = ERASE_SEQUENCE . ERASE_SEQUENCE;
}

namespace otra\console
{
  use JetBrains\PhpStorm\ArrayShape;
  use ReflectionClass;
  use ReflectionException;
  use const otra\cache\php\SPACE_INDENT;
  use const otra\console\constants\DOUBLE_ERASE_SEQUENCE;

  if (!function_exists(__NAMESPACE__ . '\\promptUser'))
  {
    /**
     * Asks the user a question again and again until the answer was correct.
     *
     * @codeCoverageIgnore
     *
     * @return string Answer.
     */
    function promptUser(string $question, string $altQuestion = '') : string
    {
      $questionAlt = DOUBLE_ERASE_SEQUENCE .
        ('' === $altQuestion
          ? 'Bad answer. ' . $question
          : $altQuestion
        );

      $userInput = askQuestion($question);

      while ('' === $userInput)
        $userInput = askQuestion($questionAlt);

      return $userInput;
    }

    /**
     * Show a question and let the user answers it.
     *
     * @codeCoverageIgnore
     * @return string
     */
    function askQuestion(string $question) : string
    {
      echo CLI_WARNING, $question, END_COLOR, PHP_EOL;

      return trim(fgets(fopen('php://stdin', 'r')));
    }

    /**
     * Loops through words to find the closest word
     *
     * @param string[] $words
     * @return array{0:?string,1:int} [$closest, $shortest]
     */
    #[ArrayShape([
      'null|string',
      'int'
    ])]
    function guessWords(string $input, array $words) : array
    {
      $closest = null;
      $shortest = -1;

      foreach ($words as $currentWord)
      {
        // Calculates the distance between the input word and the current word
        $levenshtein = levenshtein($input, $currentWord);

        // Checks for an exact match
        if (0 === $levenshtein)
        {
          $closest = $currentWord;
          $shortest = 0;
          break;
        }

        // If this distance is less than the next shortest found distance OR if a next shortest word has not yet been found
        if ($levenshtein <= $shortest || 0 >= $shortest)
        {
          $closest  = $currentWord;
          $shortest = $levenshtein;
        }
      }

      return 10 >= $shortest ? [$closest, $shortest] : [null, $shortest];
    }

    /**
     * Shows the context of the error found in a given file.
     *
     * @param string $file      Name of the file that contains the error
     * @param int    $errorLine N° of the error
     * @param int    $context   How many lines for context?
     */
    function showContext(string $file, int $errorLine, int $context) : void
    {
      $errorLine -= 1;
      $lines = file($file);
      $midContext = $context >> 1;

      // Shows the context of the error
      for ($currentLine = $errorLine - $midContext,
           $maxLines = $errorLine + $midContext;
           $currentLine <= $maxLines;
           ++$currentLine)
      {
        // if we are at the end because the portion is at the end of the file, we break the loop
        if (!isset($lines[$currentLine]))
          break;

        if (-1 !== $errorLine)
        {
          echo ($currentLine === $errorLine
            ? CLI_ERROR . ($currentLine + 1)
            : CLI_SUCCESS . ($currentLine + 1) . CLI_GRAY
          ), ' ', $lines[$currentLine];
        }
      }
    }

    /**
     * Shows the context of the error found in a given error message that appears in a given file.
     *
     * @param string $file    Name of the file that contains the error
     * @param string $error   Error to analyze
     * @param int    $context How many lines for context?
     */
    function showContextByError(string $file, string $error, int $context) : void
    {
      showContext(
        $file,
        (int)substr($error, strrpos($error, ' ', -1)),
        $context
      );
    }

    /**
     * Strips spaces, PHP7'izes the content and changes \\\\ by \\.
     * We take care of the spaces contained into folders and files names.
     * Beware, this function does not cover all the possible cases. It only works for the usages of this framework.
     *
     *
     * @return string
     */
    function shortenVarExportArray(string $code) : string
    {
      return
        str_replace(
          ['\\\\', ' => ', SPACE_INDENT . '\'', "\n", 'array (', ',' . SPACE_INDENT . SPACE_INDENT . '),',
            ',' . SPACE_INDENT . '),', '[' . SPACE_INDENT . SPACE_INDENT . '),', SPACE_INDENT,
            SPACE_INDENT . SPACE_INDENT, ',]', ',)'],
          ['\\'  , '=>'  , '\''               , ''  , '['      , ',' . SPACE_INDENT . SPACE_INDENT . '],',
            ',' . SPACE_INDENT . '],', '[],', '', '', ']', ']'],
          $code
        );
    }

    /**
     * PHP7'izes the content and changes \\\\ by \\.
     * We take care of the spaces contained into folders and files names.
     * Beware, this function does not cover all the possible cases. It only works for some usages.
     * This tool is not used by OTRA but is meant to be used in user projects.
     *
     *
     * @return string
     */
    function shortenMoreVarExportArray(string $code) : string
    {
      return preg_replace(
        '@,(\s*)]@',
        '$1]',
        str_replace(
          ['\\\\', 'array (', ',]', ',)', ')'],
          ['\\'  , '['      , ']', ']', ']'],
          $code
        )
      );
    }

    /**
     * Converts an array to a PHP "readable" array that we can put in a file to make
     * $myVar = require myFile.php;
     * A bit like `var_export` function
     *
     *
     * @throws ReflectionException
     * @return string
     */
    function convertLongArrayToShort(array $myArray) : string
    {
      if ($myArray === [])
        return '[]';

      $arrayString = '[';

      foreach($myArray as $arrayKey => $arrayItem)
      {
        if (is_string($arrayItem))
          $newArrayItem = '\'' . addslashes($arrayItem) . '\'';
        elseif (is_bool($arrayItem))
          $newArrayItem = $arrayItem ? 'true' : 'false';
        elseif (is_object($arrayItem))
          $newArrayItem = '\'' . serialize($arrayItem) . '\'';
        elseif (is_array($arrayItem))
          $newArrayItem = convertLongArrayToShort($arrayItem);
        elseif (is_null($arrayItem))
          $newArrayItem = 'null';
        else
          $newArrayItem = $arrayItem;

        $arrayString .= (is_int($arrayKey) ? $arrayKey : '\'' . $arrayKey . '\'') . '=>' . $newArrayItem . ',';
      }

      return mb_substr($arrayString, 0, -1) . ']';
    }
  }
}
