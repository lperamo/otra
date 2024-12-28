<?php
declare(strict_types=1);
namespace tests\examples\deployment\fixFiles\input\vendor
{
  use const otra\console\ERASE_SEQUENCE;

  /** This sequence moves the cursor up by 1,
   * move the cursor at the very left,
   * clears all characters from the cursor position to the end of the line (including the character at the cursor position)
   */
  const DOUBLE_ERASE_SEQUENCE = ERASE_SEQUENCE . ERASE_SEQUENCE;
}
