<?php
declare(strict_types=1);

namespace otra;
use otra\config\Routes;

/**
 * See Router::doesPatternExist for more information.
 * This function will only add debug information when we are not online.
 */
function debugRouterError(string $userUrl): void
{
  $interrogationMarkPosition = mb_strpos($userUrl, '?');
  $userUrlHasGetParameters = $interrogationMarkPosition !== false;

  if ($userUrlHasGetParameters)
    $userUrlWithoutGetParameters = substr($userUrl, 0, $interrogationMarkPosition);

  /** @var string $routeName */
  foreach (Routes::$allRoutes as $routeName => $routeData)
  {
    ?><details><summary><?= $routeName ?></summary><?php
    $endDetails = false;

    if (!in_array(
      $_SERVER['REQUEST_METHOD'],
      $routeData[Router::OTRA_ROUTE_METHOD_KEY] ?? ['GET']
    ))
    {
      ?>Bad <strong>method</strong>. Expecting : <strong><?=
      implode(',', $routeData[Router::OTRA_ROUTE_METHOD_KEY] ?? ['GET']) ?>
    </strong> and we have <strong>' . $_SERVER['REQUEST_METHOD'] . '</strong></details><?php

      $endDetails = true;
      continue;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS')
    {
      if ($_SERVER['CONTENT_TYPE'] === '')
        $_SERVER['CONTENT_TYPE'] = Router::OTRA_DEFAULT_CONTENT_TYPE;

      // We use `str_contains` to not be forced to use regexp for multipart/form-data boundaries, for example
      if (!str_contains(
        $_SERVER['CONTENT_TYPE'],
        $routeData[Router::OTRA_ROUTE_CONTENT_TYPE_KEY] ?? Router::OTRA_DEFAULT_CONTENT_TYPE)
      )
      {
        ?>Bad <strong>content type</strong>. Expecting that : <strong><?= $_SERVER['CONTENT_TYPE'] ?>
      </strong> contains <strong><?=
          $routeData[Router::OTRA_ROUTE_CONTENT_TYPE_KEY] ?? Router::OTRA_DEFAULT_CONTENT_TYPE ?>
      </strong></details><?php
        $endDetails = true;
        continue;
      }
    } elseif (!in_array(
      $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'],
      $routeData[Router::OTRA_ROUTE_METHOD_KEY] ?? ['GET']
    ))
    {
      ?>Bad <strong>HTTP_ACCESS_CONTROL_REQUEST_METHOD</strong>. Expecting that <strong><?=
      $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] ?></strong> is in <strong><?=
      implode(',', $routeData[Router::OTRA_ROUTE_METHOD_KEY] ?? ['GET']) ?>
    </strong></details><?php
      $endDetails = true;
      continue;
    }

    /** @var string $routeUrl */
    $routeUrl = $routeData[Router::OTRA_ROUTE_CHUNKS_KEY][Router::OTRA_ROUTE_URL_KEY];
    $firstBracketPosition = mb_strpos($routeUrl, '{');

    // If the route from the configuration does not contain parameters
    if ($firstBracketPosition === false)
    {
      // Is it the route we are looking for? It is the case if:
      // 1. The route from the configuration is included in the user url AND
      // 2. the user url does not have GET parameters and is equal to the route OR
      //    the user url does have GET parameters and the portion without GET parameters is equal to the route
      // AND does this user url NOT contain parameters like the route
      if (str_contains($userUrl, $routeUrl)
        && (!$userUrlHasGetParameters && $routeUrl === $userUrl
          || $userUrlHasGetParameters && $userUrlWithoutGetParameters === $routeUrl)
      )
      {
        ?></details><?php
        break;
      }
      else
      {
        ?>Wrong route. Expecting that <strong><?= $userUrl ?></strong> contains <strong><?= $routeUrl
        ?></strong> and that :<br>
        - the user url do not have GET parameters (<?= (!$userUrlHasGetParameters ? 'true' : 'false') ?>) and <strong><?=
        $userUrl ?></strong> equals to <strong><?= $routeUrl ?></strong><br>
        - OR the user url have GET parameters (<?= ($userUrlHasGetParameters ? 'true' : 'false') ?>) and <strong><?=
        ($userUrlWithoutGetParameters ?? '[variable does not exists]') ?></strong> equals to <strong><?= $routeUrl
        ?></strong></details><?php
        $endDetails = true;
      }
    }

    if (!$endDetails)
    {
      ?>The url <strong><?= $userUrl ?></strong> does not match with <strong><?= $routeUrl ?></strong>.</details><?php
    }
  }
}
