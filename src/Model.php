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
    /* @var \otra\bdd\Sql $db */
    $db = Session::get('dbConn');

    $refl = new \ReflectionObject($this);
    $props = $refl->getProperties();
    $properties = [];
    $update = false;

    foreach($props as $prop)
    {
      $name = $prop->name;
      $properties[$name] = empty($this->$name) ? null : $this->$name;

      if (str_contains($name, 'id'))
      {
        $id = $name;

        if (!empty($properties[$name]))
          $update  = true;
      }
    }
    unset($properties['table'], $props, $prop, $refl);

    if ($update === true)
    { // It's an update of the model
      $query = 'UPDATE `'. AllConfig::$dbConnections[$dbName]['db'] . '_' . $this->table . '` SET ';
      $idValue = $properties[$id];
      unset($properties[$id]);

      foreach($properties as $name => $value)
      {
        $query .= '`' . $name . '`=' ;
        $query .= (is_string($value)) ? '\'' . addslashes($value) . '\',' : $value . ' ';
      }

      $query = substr($query, 0, -1) . ' WHERE `'. $id . '`=' . $idValue;
    } else // we add a entry
    {
      unset($properties[$id]);
      $query = 'INSERT INTO `'. AllConfig::$dbConnections[$dbName]['db'] . '_' . $this->table . '` (';
      $values = '';

      foreach($properties as $name => $value)
      {
        $query .= '`' . $name . '`,';
        $values .= (is_string($value)) ? '\'' . addslashes($value) . '\',' : $value . ',';
      }
      $query = substr($query , 0, -1) . ') VALUES (' . substr($values,0,-1) . ')';
    }

    $db->fetchAssoc($db->query($query));

    return $db->lastInsertedId();
  }
}

