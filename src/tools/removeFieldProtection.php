<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\tools
 */

/**
 * Removes protection from a field in order to test it easily and returns it.
 *
 * @param class-string $class
 * @param string $field
 *
 * @return ReflectionProperty
 *
 * @throws ReflectionException
 */
function removeFieldScopeProtection(string $class, string $field) : ReflectionProperty
{
  $class = new ReflectionClass($class);
  $alteredField = $class->getProperty($field);
  $alteredField->setAccessible(true);

  return $alteredField;
}

/**
 * @param string $class
 * @param array  $fields
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
    $alteredField->setAccessible(true);
    $unprotectedFields[]= $alteredField;
  }

  return $unprotectedFields;
}

/**
 * Removes protection from a field in order to test it easily and returns it.
 *
 * @param class-string $class
 * @param string $field
 *
 * @return ReflectionProperty
 *
 * @throws ReflectionException
 */
function restoreFieldScopeProtection(string $class, string $field) : ReflectionProperty
{
  $class = new ReflectionClass($class);
  $alteredField = $class->getProperty($field);
  $alteredField->setAccessible(false);

  return $alteredField;
}

/**
 * Removes temporarily the scope protection of fields to set values.
 *
 * @param class-string $class
 * @param array        $fieldsAndValues
 *
 * @throws ReflectionException
 */
function setScopeProtectedFields(string $class, array $fieldsAndValues) : void
{
  $class = new ReflectionClass($class);

  foreach ($fieldsAndValues as $field => $value)
  {
    $alteredField = $class->getProperty($field);
    $alteredField->setAccessible(true);
    $alteredField->setValue($value);
    $alteredField->setAccessible(false);
  }
}

/**
 * Removes protection from a method in order to test it easily and returns it.
 *
 * @param class-string $class
 * @param string       $method
 *
 * @return ReflectionMethod
 *
 * @throws ReflectionException
 */
function removeMethodScopeProtection(string $class, string $method) : ReflectionMethod
{
  $method = new ReflectionMethod($class, $method);
  $method->setAccessible(true);

  return $method;
}

