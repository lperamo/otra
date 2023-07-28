<?php
/**
 * @author  Lionel Péramo
 * @package otra\tools
 */
declare(strict_types=1);

namespace otra\tools;

use SplFileObject;
use const otra\console\{ADD_BOLD, CLI_DUMP_LINE_HIGHLIGHT, CLI_LINE_DUMP, END_COLOR, REMOVE_BOLD_INTENSITY};

if (!function_exists(__NAMESPACE__ . '\\getSourceFromFile'))
{
  /**
   * Returns the portion of a file in a certain range around a specific line.
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
      $lineContent = str_replace(PHP_EOL, '', htmlentities($fileHandler->current()));
      $sourceContentMiddle = ((string) $index) . ' ' . '</i><span>' . $lineContent . '<br/></span>';
      $sourceContent .= ($index === $sourceLine)
        ? '<b>' . $sourceContentMiddle . '</b>'
        : $sourceContentMiddle;
    }

    return $sourceContent;
  }

  /**
   * Returns the portion of a file in a certain range around a specific line.
   *
   *
   * @return string
   */
  function getSourceFromFileCli(string $sourceFile, int $sourceLine, int $padding = 0, int $range = 5) : string
  {
    $fileHandler = new SplFileObject($sourceFile);
    $maxLine = $sourceLine + $range;
    $padding = str_repeat(' ', $padding);
    $sourceContent = '';

    for ($index = $sourceLine - $range; $index < $maxLine; ++$index)
    {
      $fileHandler->seek($index - 1);
      $sourceContent .= $padding . ADD_BOLD . CLI_DUMP_LINE_HIGHLIGHT;

      $sourceContent .= ($index === $sourceLine)
        ? ADD_BOLD . CLI_LINE_DUMP . ((string) $index) . ' ' . $fileHandler->current() . REMOVE_BOLD_INTENSITY . END_COLOR
        : ((string) $index) . ' ' . REMOVE_BOLD_INTENSITY . END_COLOR . $fileHandler->current();
    }

    return $sourceContent;
  }
}
