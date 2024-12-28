<?php
declare(strict_types=1);
namespace otra\tools;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

/**
 * @author Lionel Péramo
 * @package otra\tools
 */

/**
 * @param class-string $class
 * @param string[]     $fields
 *
 * @throws ReflectionException
 * @return ReflectionProperty[]
 */
function removeFieldsScopeProtection(string $class, array $fields) : array
{
  $class = new ReflectionClass($class);
  $unprotectedFields = [];

  foreach ($fields as $field)
  {
    $alteredField = $class->getProperty($field);
    $unprotectedFields[$field]= $alteredField;
  }

  return $unprotectedFields;
}

/**
 * Removes temporarily the scope protection of fields to set values.
 *
 * @param class-string         $class
 * @param array<string, mixed> $fieldsAndValues
 * @param Object|null          $objectInstance  Only needed if we need to modify non-static properties
 *
 * @throws ReflectionException
 */
function setScopeProtectedFields(string $class, array $fieldsAndValues, ?object $objectInstance = null) : void
{
  $class = new ReflectionClass($class);

  foreach ($fieldsAndValues as $field => $value)
  {
    $alteredField = $class->getProperty($field);

    if ($alteredField->isStatic())
      $alteredField->setValue($value);
    else
      $alteredField->setValue($objectInstance, $value);
  }
}
