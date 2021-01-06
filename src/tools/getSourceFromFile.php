<?php
/**
 * Returns the portion of a file in a certain range around a specific line.
 *
 * @param string $sourceFile
 * @param int    $sourceLine
 * @param int    $range
 * @param int    $padding
 *
 * @return string
 */
function getSourceFromFile(string $sourceFile, int $sourceLine, int $padding = 0, int $range = 5) : string
{
  $fileHandler = new SplFileObject($sourceFile);
  $sourceContent = '';
  $maxLine = $sourceLine + $range;
  $padding = str_repeat(' ', $padding);
  $minLine = $sourceLine - $range;

  if ($minLine < 1)
    $minLine = 1;

  for ($index = $minLine;$index < $maxLine && $fileHandler->valid(); ++$index)
  {
    $fileHandler->seek($index - 1);
    $sourceContent .= $padding . '<i>';
    $sourceContentMiddle = $index . ' ' . '</i>' . $fileHandler->current();

    $sourceContent .= ($index === $sourceLine)
      ? '<b>' . $sourceContentMiddle . '</b>'
      : $sourceContentMiddle;
  }

  return $sourceContent;
}

/**
 * Returns the portion of a file in a certain range around a specific line.
 *
 * @param string $sourceFile
 * @param int    $sourceLine
 * @param int    $range
 * @param int    $padding
 *
 * @return string
 */
function getSourceFromFileCli(string $sourceFile, int $sourceLine, int $padding = 0, int $range = 5) : string
{
  $fileHandler = new SplFileObject($sourceFile);
  $maxLine = $sourceLine + $range;
  $padding = str_repeat(' ', $padding);
  $sourceContent = '';

  for ($index = $sourceLine - $range;$index < $maxLine; ++$index)
  {
    $fileHandler->seek($index - 1);
    $sourceContent .= $padding . ADD_BOLD . CLI_BOLD_LIGHT_BLUE;

    $sourceContent .= ($index === $sourceLine)
      ? CLI_BOLD_BLUE . $index . ' ' . $fileHandler->current() . REMOVE_BOLD_INTENSITY . END_COLOR
      : $index . ' ' . REMOVE_BOLD_INTENSITY . END_COLOR . $fileHandler->current();
  }

  return $sourceContent;
}
