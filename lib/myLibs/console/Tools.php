<?
/** This sequence moves the cursor up by 1,
 * move the cursor at the very left,
 * clears all characters from the cursor position to the end of the line (including the character at the cursor position)
 */
if (false === defined('ERASE_SEQUENCE')) define('ERASE_SEQUENCE', "\033[1A\r\033[K");
if (false === defined('DOUBLE_ERASE_SEQUENCE')) define('DOUBLE_ERASE_SEQUENCE', ERASE_SEQUENCE . ERASE_SEQUENCE);

if (function_exists('promptUser') === false)
{
  /**
   * Asks the user a question again and again until the answer was correct.
   *
   * @param string $question
   * @param string $altQuestion
   *
   * @return string Answer.
   */
  function promptUser(string $question, string $altQuestion = ''): string {
    $questionAlt = DOUBLE_ERASE_SEQUENCE . ('' === $altQuestion ? 'Bad answer. ' . $question : $altQuestion);

    $line = askQuestion($question);

    while ('' === $line)
    {
      // TODO Bug with wsl ? it always loops and does not wait the input !
      $line = askQuestion($questionAlt);
    }

    return $line;
  }

  /**
   * Show a question and let the user answers it.
   *
   * @param string $question
   *
   * @return string
   */
  function askQuestion(string $question): string
  {
    echo CLI_BROWN, $question, END_COLOR, PHP_EOL;

    return trim(fgets(fopen('php://stdin', 'r')));
  }

  /**
   * Loops through words to find the closest word
   *
   * @param string $input
   * @param array  $words
   *
   * @return array [$closest, $shortest]
   */
  function guessWords(string $input, array $words) : array
  {
    $closest = null;
    $shortest = -1;

    foreach ($words as &$word)
    {
      // Calculates the distance between the input word and the current word
      $lev = levenshtein($input, $word);

      // Checks for an exact match
      if (0 === $lev)
      {
        $closest = $word;
        $shortest = 0;
        break;
      }

      // If this distance is less than the next found shortest distance OR if a next shortest word has not yet been found
      if ($lev <= $shortest || 0 >= $shortest)
      {
        $closest  = $word;
        $shortest = $lev;
      }
    }

    return 10 >= $shortest ? [$closest, $shortest] : [null, $shortest];
  }

  /**
   * Shows the context of the error found in a given file.
   *
   * @param string $file      Name of the file that contains the error
   * @param int    $errorLine N° of the error
   * @param int    $context   How much lines for context ?
   */
  function showContext(string $file, int $errorLine, int $context)
  {
    $lines = file($file);
    $midContext = (int) $context >> 1;

    // Shows the context of the error
    for ($i = $errorLine - $midContext, $max = $errorLine + $midContext; $i < $max; ++$i)
    {
      // if we are at the end because the portion is at the end of the file, we break the loop
      if (false === isset($lines[$i]))
        break;

      if(-1 !== $errorLine)
      {
        echo ($i === $errorLine
          ? CLI_RED . $i
          : CLI_GREEN . $i . CLI_LIGHT_GRAY
        ), ' ', $lines[$i];
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
  function showContextByError(string $file, string $error, int $context)
  {
    showContext(
      $file,
      (int)substr($error, strrpos($error, ' ', -1)),
      $context
    );
  }
}
?>
