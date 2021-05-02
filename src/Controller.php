<?php
/**
 * A classic MVC development controller class
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace otra;
use const otra\cache\php\{APP_ENV,CORE_PATH,DIR_SEPARATOR,PROD};

$temporaryEnv = ('cli' === PHP_SAPI ? PROD : $_SERVER[APP_ENV]);
require CORE_PATH . $temporaryEnv . DIR_SEPARATOR . ucfirst($temporaryEnv) . 'ControllerTrait.php';

if ($temporaryEnv === PROD)
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


