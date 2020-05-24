<?php
declare(strict_types=1);
/**
 * Removes protection from a field in order to test it easily and returns it.
 *
 * @param mixed $class
 * @param string $field
 *
 * @return ReflectionProperty
 *
 * @throws ReflectionException
 */
function removeFieldScopeProtection($class, string $field) : ReflectionProperty
{
  $class = new ReflectionClass($class);
  $_field = $class->getProperty($field);
  $_field->setAccessible(true);

  return $_field;
}

/**
 * Removes temporarily the scope protection of fields to set values.
 *
 * @param       $class
 * @param array $fieldsAndValues
 *
 * @throws ReflectionException
 */
function setScopeProtectedFields($class, array $fieldsAndValues) : void
{
  $class = new ReflectionClass($class);

  foreach ($fieldsAndValues as $field => &$value)
  {
    $_field = $class->getProperty($field);
    $_field->setAccessible(true);
    $_field->setValue($value);
    $_field->setAccessible(false);
  }
}

/**
 * Removes protection from a method in order to test it easily and returns it.
 *
 * @param        $class
 * @param string $method
 *
 * @return ReflectionMethod
 *
 * @throws ReflectionException
 */
function removeMethodScopeProtection($class, string $method) : ReflectionMethod
{
  $method = new ReflectionMethod($class, $method);
  $method->setAccessible(true);

  return $method;
}

