<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\helpAndTools
 */
declare(strict_types=1);

namespace otra\console\helpAndTools\routes;

use otra\config\Routes;
use otra\OtraException;
use const otra\cache\php\{BASE_PATH, BUNDLES_PATH, CACHE_PATH, CORE_PATH, DIR_SEPARATOR};
use const otra\config\VERSION;
use const otra\console\
{
  CLI_ERROR,
  CLI_INFO,
  CLI_INFO_HIGHLIGHT,
  CLI_SUCCESS,
  CLI_WARNING,
  END_COLOR
};
use function otra\tools\guessRoute;
use function otra\src\tools\{checkPHPPath,checkResourcePath};

const ROUTES_ARG_ROUTE = 2;

/**
 * @param array $argv
 *
 * @throws OtraException
 * @return void
 */
function routes(array $argv) : void
{
  if (!file_exists(BUNDLES_PATH . 'config/Routes.php'))
  {
    echo CLI_WARNING, 'No custom routes are defined.', END_COLOR, PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }
  /** Task that show all or one of the routes available for the application.
   * It shows for each related route :
   * - the url
   * - the action
   * - the resources generated
   * - the key used for the cached file names
   */

  // beware require_once only needed for automated tests
  require_once BASE_PATH . 'config/AllConfig.php';

  // 'require_once' needed instead of 'require', if we execute TasksManager::execute multiple times as in tests or some
  // scripts
  if (!defined(__NAMESPACE__ . '\\WIDTH_LEFT'))
  {
    define(__NAMESPACE__ . '\\WIDTH_LEFT', 25);
    define(__NAMESPACE__ . '\\WIDTH_MIDDLE', 10);
    // The longest text : [PHP] No other resources. [strlen(sha1('ca' . 'route' . config\AllConfig::$version . 'che'))]
    define(__NAMESPACE__ . '\\WIDTH_RIGHT', 70);
  }

  $indexLines = 0;

  // Check if we want one or all the routes
  if (isset($argv[ROUTES_ARG_ROUTE]))
  {
    require CORE_PATH . 'tools/guessRoute.php';
    $route = guessRoute($argv[ROUTES_ARG_ROUTE]);
    $routes = [$route => Routes::$allRoutes[$route]];
  }
  else
    $routes = Routes::$allRoutes;

  require CORE_PATH . 'tools/checkFilePath.php';

  /** @var array<string,array<string, array<int|string,string|array>>> $routes */
  foreach($routes as $route => $details)
  {
    if ('otra_exception' === $route )
      continue;

    // Routes and paths management
    $chunks = $details['chunks'];
    $altColor = ($indexLines % 2) ? CLI_INFO : CLI_INFO_HIGHLIGHT;
    echo $altColor, sprintf('%-' . WIDTH_LEFT . 's', $route),
    str_pad('Url', WIDTH_MIDDLE), ': ' , $chunks[Routes::ROUTES_CHUNKS_URL], PHP_EOL;

    echo str_pad(' ', WIDTH_LEFT),
    str_pad('Path', WIDTH_MIDDLE),
      ': ' . $chunks[Routes::ROUTES_CHUNKS_BUNDLE] . DIR_SEPARATOR . $chunks[Routes::ROUTES_CHUNKS_MODULE] . DIR_SEPARATOR .
      $chunks[Routes::ROUTES_CHUNKS_CONTROLLER] . 'Controller/' . $chunks[Routes::ROUTES_CHUNKS_ACTION],
    PHP_EOL;

    // shaName is the encrypted key that match a particular route / version
    $shaName = sha1('ca' . $route . VERSION . 'che');

    echo str_pad(' ', WIDTH_LEFT), 'Resources : ';

    // Resources management : show the state of each resource. Red => missing, green => exists
    if (isset($details['resources']))
    {
      $resources = $details['resources'];

      if (!isset($resources['template']))
        echo (checkPHPPath(CACHE_PATH, $route) ? CLI_SUCCESS : CLI_ERROR), '[PHP]', $altColor;

      if (isset($resources['app_css'])
        || isset($resources['bundle_css'])
        || isset($resources['core_css'])
        || isset($resources['module_css']))
      {
        echo (checkResourcePath('css',CACHE_PATH, $shaName)
          ? CLI_SUCCESS
          : CLI_ERROR),
        '[SCREEN CSS]', $altColor;

        echo (checkResourcePath('css',CACHE_PATH, 'print_' . $shaName)
          ? CLI_SUCCESS
          : CLI_ERROR),
        '[PRINT CSS]', $altColor;
      }

      if (isset($resources['app_js'])
        || isset($resources['bundle_js'])
        || isset($resources['core_js'])
        || isset($resources['module_js'])
      )
      {
        echo (checkResourcePath('js',CACHE_PATH, $shaName)
          ? CLI_SUCCESS
          : CLI_ERROR),
        '[JS]', $altColor;
      }

      if (isset($resources['template']))
      {
        echo (checkResourcePath('tpl',CACHE_PATH, $shaName)
          ? CLI_SUCCESS
          : CLI_ERROR),
        '[TEMPLATE]', $altColor;
      }
    } else
    {
      echo (checkPHPPath(CACHE_PATH, $route) ? CLI_SUCCESS : CLI_ERROR), '[PHP]', $altColor,
      ' No other resources. ';
    }

    echo '[', $shaName, ']', PHP_EOL, END_COLOR;

    // We only show a decoration line if it's not the last route
    end($routes);

    if ($route !== key($routes))
      echo str_repeat('-', WIDTH_LEFT + WIDTH_MIDDLE + WIDTH_RIGHT), PHP_EOL;

    ++$indexLines;
  }
}
