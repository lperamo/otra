<?php

namespace bundles\CMS\models;

/**
 * LPCMS Footer model
 *
 * @author Lionel Péramo
 */
class Footer
{
  /**
   * @return $headers
   */
  public static function getAll($db)
  {
    return $db->values($db->query('SELECT * FROM lpcms_footer'));
  }
}
?>
