<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\deployment
 */
declare(strict_types=1);

namespace otra\console\deployment\genSitemap;

use otra\config\AllConfig;
use const otra\cache\php\{BASE_PATH, BUNDLES_PATH, SPACE_INDENT};
use const otra\console\SUCCESS;

const
  SPACE_INDENT_2 = SPACE_INDENT . SPACE_INDENT;

/**
 * @return void
 */
function genSitemap() : void
{
  $routes = require BUNDLES_PATH . 'config/Routes.php';
  $siteMapContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL .
    '<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

  foreach ($routes as $route)
  {
    $siteMapContent .= /** @lang text */
      SPACE_INDENT . '<url>' . PHP_EOL .
      SPACE_INDENT_2 . '<loc>https://' . AllConfig::${$_SERVER['APP_SCOPE']}['domainName'] . $route['chunks']['0'] .
      '</loc>' . PHP_EOL .
      SPACE_INDENT_2 . '<lastmod>' . date('c') . '</lastmod>' . PHP_EOL .
      SPACE_INDENT . '</url>';
  }

  file_put_contents(BASE_PATH . 'web/sitemap.xml', /** @lang text */ $siteMapContent . PHP_EOL . '</urlset>');

  echo 'Site map created' . SUCCESS;
}
