<?
/** A MVC dynamic controller loader class
 *
 * @author Lionel Péramo */
if('cli' == php_sapi_name())
  require 'prod' . DS . 'Controller.php';
else
  require XMODE . DS . 'Controller.php'; ?>
