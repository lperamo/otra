<?
/**
 * Removes protection from a field in order to test it easily
 *
 * @param mixed $class
 * @param string $field
 *
 * @return ReflectionProperty
 *
 * @throws ReflectionException
 */
function removesFieldScopeProtection($class, string $field) : ReflectionProperty
{
  $class = new ReflectionClass($class);
  $_field = $class->getProperty($field);
  $_field->setAccessible(true);

  return $_field;
}
?>
