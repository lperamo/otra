<?
/**
 * Initialisation class
 *
 * @author Lionel Péramo */

namespace bundles\CMS;

use lib\myLibs\core\Session,
	  lib\myLibs\core\bdd\Sql;

class Init
{
  public static function Init() {
    Session::set('db', 'CMS');
    Session::set('dbConn', Sql::getDB('Mysql'));
  }
}
?>
