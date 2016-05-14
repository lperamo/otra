<?php

namespace lib\myLibs;

use config\All_Config;
use lib\myLibs\Session;

/**
 * A classic MVC model class
 *
 * @author Lionel Péramo
 */
class Model
{
  public function get($property) { return $this->$property; }

  public function set($property, $value) { $this->$property = $value; }

  /**
   * Save or update if the id is known
   *
   * @return int The last id used
   */
  public function save()
  {

    $dbName = Session::get('db');
    /* @var $db lib\myLibs\bdd\Sql */
    $db = Session::get('dbConn');
//    $db instanceof lib\myLibs\bdd\Sql;
    $db->selectDb();

    $refl = new \ReflectionObject($this);
    $props = $refl->getProperties();
    $properties = array();

    foreach($props as $prop)
    {
      $name = $prop->name;
      $properties[$prop->name] = (empty($this->$name)) ? null : $this->$name;
      if(strpos($name, 'id') !== false)
      {
        $id = $name;
        if(!empty($properties[$name]))
          $update  = true;
      }
    }
    unset($properties['table'], $props, $prop, $refl);

    if($update)
    { // It's an update
      $query = 'UPDATE `'. All_Config::$dbConnections[$dbName]['db'] . '_' . $this->table . '` SET ';
      $idValue = $properties[$id];
      unset($properties[$id]);
      foreach($properties as $name => $value)
      {
        $query .= '`' . $name . '`=' ;
        $query .= (is_string($value)) ? '\'' . addslashes($value) . '\',' : $value . ' ';
      }

      $query = substr($query, 0, -1) . ' WHERE `'. $id . '`=' . $idValue;
    }else // we add a entry
    {
      unset($properties[$id]);
      $query = 'INSERT INTO `'. All_Config::$dbConnections[$dbName]['db'] . '_' . $this->table . '` (';
      $values = '';
      foreach($properties as $name => $value)
      {
        $query .= '`' . $name . '`,';
        $values .= (is_string($value)) ? '\'' . addslashes($value) . '\',' : $value . ',';
      }
      $query = substr($query , 0, -1) . ') VALUES (' . substr($values,0,-1) . ')';
    }

    $db->fetchAssoc($db->query($query));

    // echo $db->lastInsertedId();die;
    return $db->lastInsertedId();
  }
}
