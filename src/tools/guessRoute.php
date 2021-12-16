<?php
declare(strict_types=1);
namespace otra\tools;

use otra\config\Routes;
use otra\OtraException;
use const otra\cache\php\CONSOLE_PATH;
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_WARNING, END_COLOR};
use function otra\{console\promptUser, console\guessWords};

function guessRoute(string $route) : string
{
  if (isset(Routes::$allRoutes[$route]))
    return $route;

  // We try to find a route which the name is similar
  // `require_once` 'cause maybe the user type a wrong task like 'genBootstrap' so we have already loaded this src !
  require_once CONSOLE_PATH . 'tools.php';
  [$newRoute] = guessWords($route, array_keys(Routes::$allRoutes));

  // And asks the user whether we find what he wanted or not
  $choice = promptUser('There are no route with the name ' . CLI_BASE . $route . CLI_WARNING
    . ' ! Do you mean ' . CLI_BASE . $newRoute . CLI_WARNING . ' ? (y/n)');

  // If our guess is wrong, we apologise and exit !
  if ('n' === $choice)
  {
    echo CLI_ERROR, 'Sorry then !', END_COLOR, PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }

  return $newRoute;
}
