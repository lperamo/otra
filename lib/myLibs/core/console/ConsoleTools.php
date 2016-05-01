<?
/** This sequence moves the cursor up by 1,
 * move the cursor at the very left,
 * clears all characters from the cursor position to the end of the line (including the character at the cursor position)
 */
define('ERASE_SEQUENCE', "\033[1A\r\033[K");
define('DOUBLE_ERASE_SEQUENCE', ERASE_SEQUENCE . ERASE_SEQUENCE);
/**
 * Asks the user a question again and again until the answer was correct.
 *
 * @param string $question
 * @param string $altQuestion
 *
 * @return string Answer.
 */
function promptUser(string $question, string $altQuestion = '') : string
{
  $questionAlt = DOUBLE_ERASE_SEQUENCE . ('' === $altQuestion
    ? 'Bad answer. ' . $question
    : $altQuestion
  );

  $line = askQuestion($question);

  while ('' === $line) {
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
function askQuestion(string $question) : string
{
  echo brownText($question), PHP_EOL;

  return trim(fgets(fopen('php://stdin', 'r')));
}

/**
 * Execute a CLI command. (Passthru displays more things than system)
 *
 * @param string  $cmd     Command to pass
 * @param integer $verbose Verbose mode (0,1 or 2)
 *
 * @return bool Success or fail ?
 */
function cli($cmd, $verbose = 1) { (0 < $verbose) ? passthru($cmd, $return) : exec($cmd, $return); return $return; }
?>
