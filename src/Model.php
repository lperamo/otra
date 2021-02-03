<?php
declare(strict_types=1);

namespace otra;

use config\AllConfig;

/**
 * A classic MVC model class
 *
 * @author Lionel Péramo
 */
abstract class Model
{
  private string $table;

  /**
   * @param $property
   *
   * @return mixed
   */
  public function get($property) { return $this->$property; }

  /**
   * @param $property
   * @param $value
   */
  public function set($property, $value) { $this->$property = $value; }

  /**
   * Save or update if the id is known
   *
   * @return int The last id used
   */
  public function save()
  {
    $dbName = Session::get('db');
    /* @var \otra\bdd\Sql $dbConn */
    $dbConn = Session::get('dbConn');

    $reflectedObject = new \ReflectionObject($this);
    $reflectedProperties = $reflectedObject->getProperties();
    $computedProperties = [];
    $update = false;

    foreach($reflectedProperties as $reflectedProperty)
    {
      $propertyName = $reflectedProperty->name;
      $computedProperties[$propertyName] = empty($this->$propertyName) ? null : $this->$propertyName;

      if (str_contains($propertyName, 'id'))
      {
        $id = $propertyName;

        if (!empty($computedProperties[$propertyName]))
          $update  = true;
      }
    }
    unset($computedProperties['table'], $reflectedProperties, $propertyName, $reflectedProperty, $reflectedObject);

    if ($update === true)
    { // It's an update of the model
      $query = 'UPDATE `'. AllConfig::$dbConnections[$dbName]['db'] . '_' . $this->table . '` SET ';
      $idValue = $computedProperties[$id];
      unset($computedProperties[$id]);

      foreach($computedProperties as $propertyName => $value)
      {
        $query .= '`' . $propertyName . '`=' ;
        $query .= (is_string($value)) ? '\'' . addslashes($value) . '\',' : $value . ' ';
      }

      $query = substr($query, 0, -1) . ' WHERE `'. $id . '`=' . $idValue;
    } else // we add a entry
    {
      unset($computedProperties[$id]);
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

