<?php

namespace bundles\CMS;

use lib\myLibs\core\Session,
	lib\myLibs\core\bdd\Sql,
	config\All_Config;

/**
 * Description of Init
 *
 * @author Lionel Péramo
 */
class Init
{
  public static function Init()
  {
    Session::set('db', 'CMS');
		Session::set('dbConn', Sql::getDB('Mysql'));
  }
}
?>
