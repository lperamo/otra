<?php

namespace bundles\CMS\models;

/**
 * LPCMS Module model
 *
 * @author Lionel Péramo
 */
class Module
{
  /**
   * @return $headers
   */
  public static function getAll($db)
  {
    return $db->values($db->query('SELECT * FROM lpcms_module'));
  }
}
?>
