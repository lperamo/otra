<?php
/**
 * Here we control which controller to load according to the environment
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace otra;
use const otra\cache\php\{APP_ENV,CORE_PATH,DIR_SEPARATOR};

$temporaryEnv = $_SERVER[APP_ENV];
require CORE_PATH . $temporaryEnv . DIR_SEPARATOR . ucfirst($temporaryEnv) . 'ControllerTrait.php';
require CORE_PATH . $temporaryEnv . DIR_SEPARATOR . 'Controller.php';
unset($temporaryEnv);

