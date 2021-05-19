<?php
declare(strict_types=1);
namespace otra;
use otra\config\AllConfig;
use otra\bdd\Sql;
use ReflectionObject;

/**
 * A classic MVC model class
 *
 * @author Lionel Péramo
 * @package otra
 */
abstract class Model
{
  private string $table;

  /**
   * @param string $property
   *
   * @return mixed
   */
  public function get(string $property) : mixed { return $this->$property; }

  /**
   * @param string $property
   * @param mixed $value
   */
  public function set(string $property, mixed $value) : void { $this->$property = $value; }

  /**
   * Save or update if the id is known
   *
   * @return string The last id used
   */
  public function save() : string
  {
    $dbName = Session::get('db');
    /* @var Sql $dbConn */
    $dbConn = Session::get('dbConn');

    $reflectedObject = new ReflectionObject($this);
    $reflectedProperties = $reflectedObject->getProperties();
    $computedProperties = [];
    $isUpdate = false;

    foreach($reflectedProperties as $reflectedProperty)
    {
      $propertyName = $reflectedProperty->name;
      $computedProperties[$propertyName] = empty($this->$propertyName) ? null : $this->$propertyName;

      if (str_contains($propertyName, 'id'))
      {
        $identifier = $propertyName;

        if (!empty($computedProperties[$propertyName]))
          $isUpdate  = true;
      }
    }
    unset($computedProperties['table'], $reflectedProperties, $propertyName, $reflectedProperty, $reflectedObject);

    if ($isUpdate)
    { // It's an update of the model
      $query = 'UPDATE `'. AllConfig::$dbConnections[$dbName]['db'] . '_' . $this->table . '` SET ';
      $idValue = $computedProperties[$identifier];
      unset($computedProperties[$identifier]);

      foreach($computedProperties as $propertyName => $value)
      {
        $query .= '`' . $propertyName . '`=' ;
        $query .= (is_string($value)) ? '\'' . addslashes($value) . '\',' : $value . ' ';
      }

      $query = substr($query, 0, -1) . ' WHERE `'. $identifier . '`=' . $idValue;
    } else // we add a entry
    {
      unset($computedProperties[$identifier]);
      $query = 'INSERT INTO `'. AllConfig::$dbConnections[$dbName]['db'] . '_' . $this->table . '` (';
      $values = '';

      foreach($computedProperties as $propertyName => $value)
      {
        $query .= '`' . $propertyName . '`,';
        $values .= (is_string($value)) ? '\'' . addslashes($value) . '\',' : $value . ',';
      }
      $query = substr($query , 0, -1) . ') VALUES (' . substr($values,0,-1) . ')';
    }

    $dbConn->fetchAssoc($dbConn->query($query));

    return $dbConn->lastInsertedId();
  }
}

