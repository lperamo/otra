<?php
declare(strict_types=1);
const BUNDLE_MASK_QUESTION_BEGINNING = 'Do you want to associate ';
define('BUNDLE_MASK_QUESTION_END', ' with that bundle ' . END_COLOR . CLI_LIGHT_CYAN . $bundleName . CLI_YELLOW . ' (n or y)?');
$bundleMask = 0; // By default, we create 0 additional folders

foreach(BUNDLE_FOLDERS as $key => &$folder)
{
  $question = BUNDLE_MASK_QUESTION_BEGINNING . CLI_LIGHT_CYAN . $folder . CLI_YELLOW . BUNDLE_MASK_QUESTION_END;
  $answer = promptUser($question);

  while ('n' !== $answer && 'y' !== $answer)
  {
    $answer = promptUser('Bad answer. ' . $question);
    // We clean the screen
    echo ERASE_SEQUENCE;
  }

  $bundleMask += pow(2, $key) * ($answer === 'y' ? 1 : 0);
}
