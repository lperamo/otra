<?
/**
 * Asks the user a question.
 *
 * @param string $question
 *
 * @return string Answer.
 */
function promptUser(string $question) : string
{
  echo brownText($question), PHP_EOL;
  $handle = fopen ('php://stdin', 'r');
  $line = trim(fgets($handle));

  if ('' === $line){
    echo 'ABORTING !', PHP_EOL;
    exit(1);
  }

  return $line;
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
