<?php
/**
 * A classic MVC development controller class
 *
 * @author Lionel Péramo */
declare(strict_types=1);

namespace otra;

use otra\MasterController;

$temporaryEnv = ('cli' === PHP_SAPI ? 'prod' : $_SERVER['APP_ENV']);
require CORE_PATH . $temporaryEnv . '/ControllerTrait.php';
unset($temporaryEnv);

class Controller extends MasterController
{
  use ControllerTrait;
}
?>
