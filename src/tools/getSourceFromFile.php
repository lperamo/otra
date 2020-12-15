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
function getSourceFromFile(string $sourceFile, int $sourceLine, int $range = 5, int $padding) : string
{
  $fileHandler = new SplFileObject($sourceFile);
  $sourceContent = '';
  $maxLine = $sourceLine + $range;
  $padding = str_repeat(' ', $padding);

  for ($index = $sourceLine - $range;$index < $maxLine; ++$index)
  {
    $fileHandler->seek($index - 1);
    $currentLine = $fileHandler->current();

    $sourceContent .= $padding . '<i>' . $index . '</i> ' .
      ($index === $sourceLine
      ? '<b>' . $currentLine . '</b>'
      : $currentLine);
  }

  return $sourceContent;
}
