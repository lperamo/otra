<?php
declare(strict_types=1);

namespace otra\tools;

function isSerialized(mixed $dataToCheck) : bool
{
  // If it isn't a string, it isn't serialized.
  if (!is_string($dataToCheck))
    return false;

  $dataToCheck = trim($dataToCheck);

  if ('N;' === $dataToCheck)
    return true;

  if (strlen($dataToCheck) < 4
    || ':' !== $dataToCheck[1]
    || (';' !== $dataToCheck[-1] && '}' !== $dataToCheck[-1])
  )
    return false;

  $token = $dataToCheck[0];

  if ($token === 's' && '"' !== $dataToCheck[-2])
    return false;

  if (in_array($token, ['s', 'a', 'O']))
    return (bool)preg_match('/^' . $token . ':[0-9]+:/s', $dataToCheck);

  if (in_array($token, ['b', 'i', 'd']))
    return (bool) preg_match( "/^' . $token . ':[0-9.E+-]+;$/", $dataToCheck );

  return false;
}
