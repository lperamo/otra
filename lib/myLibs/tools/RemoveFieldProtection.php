<?php
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
?>
