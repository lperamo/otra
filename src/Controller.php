<?php
/**
 * A classic MVC development controller class
 *
 * @author Lionel Péramo */
declare(strict_types=1);

namespace otra;

$temporaryEnv = ('cli' === PHP_SAPI ? 'prod' : $_SERVER[APP_ENV]);
require CORE_PATH . $temporaryEnv . '/' . ucfirst($temporaryEnv) . 'ControllerTrait.php';

if ($temporaryEnv === 'prod')
{
  /**
   * Production controller
   * @package otra
   */
  class Controller extends MasterController
  {
    use ProdControllerTrait;
  }
} else
{
  /**
   * Development controller
   * @package otra
   */
  class Controller extends MasterController
  {
    use DevControllerTrait;
  }
}

unset($temporaryEnv);


