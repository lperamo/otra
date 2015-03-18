<?php

namespace bundles\CMS\models;

/**
 * LPCMS Header model
 *
 * @author Lionel Péramo
 */
class Header
{
  /**
   * @return $headers
   */
  public static function getAll($db)
  {
    return $db->values($db->query('SELECT * FROM lpcms_header'));
  }
}
?>
