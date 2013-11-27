<?php
/** An universal autoloader !
 * @author Lionel Péramo */
spl_autoload_register(function($className) use($classMap){ require $classMap[$className]; });
?>
