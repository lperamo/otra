<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\architecture
 */

const BUNDLE_MASK_QUESTION_BEGINNING = 'Do you want to associate ';
/** @var string $bundleName */
define(
  'BUNDLE_MASK_QUESTION_END',
  ' with that bundle ' . END_COLOR . CLI_LIGHT_CYAN . $bundleName . CLI_YELLOW . ' (n or y)?'
);
$bundleMask = 0; // By default, we create 0 additional folders

/**
 * @var int    $numericKey
 * @var string $folder
 */
foreach(BUNDLE_FOLDERS as $numericKey => $folder)
{
  $question = BUNDLE_MASK_QUESTION_BEGINNING . CLI_LIGHT_CYAN . $folder . CLI_YELLOW . BUNDLE_MASK_QUESTION_END;
  $answer = promptUser($question);

  while ('n' !== $answer && 'y' !== $answer)
  {
    $answer = promptUser('Bad answer. ' . $question);
    // We clean the screen
    echo ERASE_SEQUENCE;
  }

  $bundleMask += pow(2, $numericKey) * ($answer === 'y' ? 1 : 0);
}
