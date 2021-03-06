<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console
 */

/** This sequence moves the cursor up by 1,
 * move the cursor at the very left,
 * clears all characters from the cursor position to the end of the line (including the character at the cursor position)
 */
if (!defined('ERASE_SEQUENCE'))
  define('ERASE_SEQUENCE', "\033[1A\r\033[K");

if (!defined('DOUBLE_ERASE_SEQUENCE'))
  define('DOUBLE_ERASE_SEQUENCE', ERASE_SEQUENCE . ERASE_SEQUENCE);

if (!function_exists('promptUser'))
{
  /**
   * Asks the user a question again and again until the answer was correct.
   *
   * @param string $question
   * @param string $altQuestion
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
    {
      // TODO Bug with wsl ? it always loops and does not wait the input !
      $userInput = askQuestion($questionAlt);
    }

    return $userInput;
  }

  /**
   * Show a question and let the user answers it.
   *
   * @param string $question
   * @codeCoverageIgnore
   *
   * @return string
   */
  function askQuestion(string $question) : string
  {
    echo CLI_YELLOW, $question, END_COLOR, PHP_EOL;

    return trim(fgets(fopen('php://stdin', 'r')));
  }

  /**
   * Loops through words to find the closest word
   *
   * @param string   $input
   * @param string[] $words
   *
   * @return array [$closest, $shortest]
   */
  #[\JetBrains\PhpStorm\ArrayShape([
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

      // If this distance is less than the next found shortest distance OR if a next shortest word has not yet been found
      if ($levenshtein <= $shortest || 0 >= $shortest)
      {
        $closest  = $currentWord;
        $shortest = $levenshtein;
      }
    }

    return 10 >= $shortest ? [$closest, $shortest] : [null, $shortest];
  }

  /**
   * TODO we must use fgets instead and create the array as you go in order to not store the entire file into an array
   * to save memory.
   * Shows the context of the error found in a given file.
   *
   * @param string $file      Name of the file that contains the error
   * @param int    $errorLine N° of the error
   * @param int    $context   How much lines for context ?
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
          ? CLI_RED . ($currentLine + 1)
          : CLI_GREEN . ($currentLine + 1) . CLI_LIGHT_GRAY
        ), ' ', $lines[$currentLine];
      }
    }
  }

  /**
   * Shows the context of the error found in a given error message that appears in a given file.
   *
   * @param string $file    Name of the file that contains the error
   * @param string $error   Error to analyze
   * @param int    $context How much lines for context ?
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
   * Beware, this function do not cover all the possibles cases. It only works for the usages of this framework.
   *
   * @param string $code
   *
   * @return string
   */
  function convertArrayFromVarExportToShortVersion(string $code) : string
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
   * Beware, this function do not cover all the possibles cases. It only works for some usages.
   * This tool is not used by OTRA but is meant to be used in user projects.
   *
   * @param string $code
   *
   * @return string
   */
  function convertArrayFromVarExportToShorterVersion(string $code) : string
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
}

