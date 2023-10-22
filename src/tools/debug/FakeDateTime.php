<?php
declare(strict_types=1);
namespace otra\tools\debug;

use DateTime;

/**
 * Class that defines a fake DateTime class as the default one does not handle reflection when we want to get the
 * properties.
 *
 * @author Lionel Péramo
 * @package otra
 */
readonly class FakeDateTime
{
  private string
    $date,
    $timezone;
  private int $timezone_type;

  public function __construct(DateTime $dateTimeInstance)
  {
    // Awful code to clean
    ob_start();
    var_dump($dateTimeInstance);
    $awfulThing = ob_get_clean();
    preg_match('@timezone_type"\]\=\>\s+int\(([1-9]+)\)@', $awfulThing, $awfulMatches);

    $this->date = $dateTimeInstance->format('Y-m-d H:i:s.u');
    $this->timezone = $dateTimeInstance->getTimezone()->getName();
    $this->timezone_type = (int) $awfulMatches[1];
  }
}
