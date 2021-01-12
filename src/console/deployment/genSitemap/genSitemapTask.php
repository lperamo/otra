<?php
declare(strict_types=1);
$routes = require BASE_PATH . 'bundles/config/Routes.php';
$siteMapContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL .
  '<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

define('SPACE_INDENT_2', SPACE_INDENT . SPACE_INDENT);

foreach ($routes as $route)
{
  $siteMapContent .= /** @lang text */
    SPACE_INDENT . '<url>' . PHP_EOL .
    SPACE_INDENT_2 . '<loc>https://' . \config\AllConfig::$deployment['domainName'] . $route['chunks']['0'] .
    '</loc>' . PHP_EOL .
    SPACE_INDENT_2 . '<lastmod>' . date('c') . '</lastmod>' . PHP_EOL .
    SPACE_INDENT . '</url>';
}

file_put_contents(BASE_PATH . 'web/sitemap.xml', /** @lang text */ $siteMapContent . PHP_EOL . '</urlset>');

