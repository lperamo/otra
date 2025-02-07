<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\deployment
 */
declare(strict_types=1);

namespace otra\console\deployment\genServerConfig;

use otra\config\AllConfig;
use JetBrains\PhpStorm\Pure;
use otra\config\Routes;
use otra\OtraException;
use const otra\cache\php\{DEV,SPACE_INDENT};
use const otra\console\{CLI_BASE, CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR};
use const otra\config\VERSION;

/** @var string $fileName */

const
  OTRA_LABEL_RETURN_403 = 'return 403;',
  OPENING_BRACKET = SPACE_INDENT . '{' . PHP_EOL,
  ENDING_BRACKET = SPACE_INDENT . '}' . PHP_EOL,
  OPENING_BRACKET_2 = SPACE_INDENT_2 . '{' . PHP_EOL,
  ENDING_BRACKET_2 = SPACE_INDENT_2 . '}' . PHP_EOL,
  OTRA_LABEL_RETURN_403_BLOCK_2 = OPENING_BRACKET_2 .
    SPACE_INDENT_3 . OTRA_LABEL_RETURN_403 . PHP_EOL .
    ENDING_BRACKET_2,
  OTRA_LABEL_TYPES = 'types',
  OTRA_LABEL_ROOT_PATH = 'root $rootPath;',
  CHECK_HTTP_REFERER_2 = SPACE_INDENT_2 . 'if ($http_referer = "")' . PHP_EOL . OTRA_LABEL_RETURN_403_BLOCK_2,
  LABEL_TYPES_2 = SPACE_INDENT_2 . OTRA_LABEL_TYPES . PHP_EOL,
  COMPRESSED_HEADER_2 = SPACE_INDENT_2 . 'add_header Content-Encoding br;' . PHP_EOL,
  LABEL_APPLICATION_JSON_3 = SPACE_INDENT_3 . 'application/json map;' . PHP_EOL,
  KEY_CORE = 'core',
  KEY_NO_NONCES = 'noNonces',
  KEY_RESOURCES = 'resources',
  KEY_TEMPLATE = 'template';

/**
 * @return string
 */
#[Pure] function handlesHTTPSRedirection() : string
{
  return 'server' . PHP_EOL .
    '{' . PHP_EOL .
    SPACE_INDENT . str_pad('listen', GEN_SERVER_CONFIG_STR_PAD) . '80;' . PHP_EOL .
    SPACE_INDENT . str_pad('listen', GEN_SERVER_CONFIG_STR_PAD) . '[::]:80;' . PHP_EOL .
    SPACE_INDENT . '# Updates the server_name if needed' . PHP_EOL .
    SPACE_INDENT . str_pad('server_name', GEN_SERVER_CONFIG_STR_PAD) . GEN_SERVER_CONFIG_SERVER_NAME .
    ';' . PHP_EOL .
    SPACE_INDENT . str_pad('return', GEN_SERVER_CONFIG_STR_PAD) .
    '301 https://$server_name$request_uri; #Redirection' . PHP_EOL .
    '}' . PHP_EOL;
}

/**
 * Removes XSS useless protection for this kind of resource.
 * Enforces a right Referrer-Policy, HSTS, sniff protection and server information hiding.
 *
 * @return string
 */
function addingSecurityAdjustments() : string
{
  return SPACE_INDENT_2 . 'add_header X-XSS-Protection "";' . PHP_EOL .
    SPACE_INDENT_2 . 'add_header Referrer-Policy same-origin always;' . PHP_EOL .
    SPACE_INDENT_2 . 'add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload";' . PHP_EOL .
    SPACE_INDENT_2 . 'add_header X-Content-Type-Options "nosniff";' . PHP_EOL .
    SPACE_INDENT_2 . 'server_tokens off;' . PHP_EOL;
}

/**
 * @return string
 */
#[Pure] function handleCompressedAsset(string $assetType = 'css'): string
{
  $mimeType = [
    'css' => 'text/css',
    'js' => 'application/javascript',
    'tpl' => 'text/html'
  ][$assetType];

  $content = SPACE_INDENT . '# Handling cached ' . strtoupper($assetType) .
    ' (PWA uses them even when we are in development mode)'. PHP_EOL .
    SPACE_INDENT . 'location ~ /cache/' . $assetType . '/.*\.br$' . PHP_EOL .
    OPENING_BRACKET .
    CHECK_HTTP_REFERER_2 . PHP_EOL .
    LABEL_TYPES_2 .
    OPENING_BRACKET_2 .
    SPACE_INDENT_3 . $mimeType . ' br;' . PHP_EOL .
    ENDING_BRACKET_2 .
    PHP_EOL;

  // brotli_types has the text/html set by default, so no need to add it again
  if ($assetType !== 'tpl')
    $content .= SPACE_INDENT_2 . 'brotli_types ' . $mimeType . ';' . PHP_EOL;

  return $content . COMPRESSED_HEADER_2 .
    addingSecurityAdjustments() .
    PHP_EOL .
    SPACE_INDENT_2 . OTRA_LABEL_ROOT_PATH . PHP_EOL .
    ENDING_BRACKET;
}

/**
 * Only shows images if we do not access them directly
 *
 * @return string
 */
#[Pure] function handleWebFolderAssets() : string
{
  return SPACE_INDENT . '# Handling service worker, robots.txt and sitemaps' . PHP_EOL .
    SPACE_INDENT . 'location ~ /.*\.(js|txt|xml)$' . PHP_EOL .
    OPENING_BRACKET .
    CHECK_HTTP_REFERER_2 .
    ENDING_BRACKET .
    PHP_EOL .
    SPACE_INDENT . '# Handling images and fonts' . PHP_EOL .
    SPACE_INDENT . 'location ~ /.*\.(avif|ico|jpe?g|png|svg|webp|woff2)$' . PHP_EOL .
    OPENING_BRACKET .
    SPACE_INDENT_2 .
    '# Workaround, Firefox does not send referrer for favicons so we send always the images when Firefox is used' .
    PHP_EOL .
    PHP_EOL .
    SPACE_INDENT_2 . '# Handles the case of "not favicon images" when using Firefox' . PHP_EOL .
    SPACE_INDENT_2 . 'set $referer $http_referer;' . PHP_EOL.
    PHP_EOL .
    SPACE_INDENT_2 . 'if ($uri ~ "^/(favicon|apple-touch).*\.png$")' . PHP_EOL .
    OPENING_BRACKET_2 .
    SPACE_INDENT_3 . 'set $referer https://$server_name/;' . PHP_EOL .
    ENDING_BRACKET_2 .
    PHP_EOL .
    SPACE_INDENT_2 . 'if ($http_user_agent !~ ".*Firefox.*")' . PHP_EOL .
    OPENING_BRACKET_2 .
    SPACE_INDENT_3 . 'set $referer $http_referer;' . PHP_EOL .
    ENDING_BRACKET_2 .
    PHP_EOL .
    SPACE_INDENT_2 . 'if ($referer = "")' . PHP_EOL .
    OTRA_LABEL_RETURN_403_BLOCK_2 .
    PHP_EOL .
    SPACE_INDENT_2 . '# Handle vendor images' . PHP_EOL .
    SPACE_INDENT_2 . 'if ($uri ~ "^/vendor.*$")' . PHP_EOL .
    OPENING_BRACKET_2 .
    SPACE_INDENT_3 . OTRA_LABEL_ROOT_PATH . PHP_EOL .
    ENDING_BRACKET_2 .
    ENDING_BRACKET;
}

/**
 * Handle PHP requests
 *
 * @return string
 */
function handleRewriting() : string
{
  $rewriteRules = SPACE_INDENT . '# Handles rewriting' . PHP_EOL .
    SPACE_INDENT . 'location /' . PHP_EOL .
    OPENING_BRACKET;

  $rewriteRules .= SPACE_INDENT_2;
  $addedRules = false;

  foreach (Routes::$allRoutes as $route => $routeConfig)
  {
    if (isset($routeConfig[KEY_CORE]) && $routeConfig[KEY_CORE])
      continue;

    // If it is a static page without nonces (generated dynamically), then we can deliver directly the (compressed)
    // template. `substr` is used to remove the first slash.
    if (isset($routeConfig[KEY_RESOURCES][KEY_NO_NONCES])
      && isset($routeConfig[KEY_RESOURCES][KEY_TEMPLATE])
      && $routeConfig[KEY_RESOURCES][KEY_TEMPLATE] && $routeConfig[KEY_RESOURCES][KEY_NO_NONCES])
    {
      $rewriteRules .= 'rewrite ' . substr($routeConfig['chunks'][Routes::ROUTES_CHUNKS_URL], 1) . ' /static/' .
        sha1('ca' . $route . VERSION . 'che') . '.br last;' . PHP_EOL;
      $addedRules = true;
    }
  }

  if ($addedRules)
    $rewriteRules .= SPACE_INDENT_2;

  $rewriteRules .= 'rewrite ^ /index';

  if (GEN_SERVER_CONFIG_ENVIRONMENT === DEV)
    $rewriteRules .= ucfirst(DEV);

  return $rewriteRules . '.php last;' . PHP_EOL . ENDING_BRACKET .
    PHP_EOL .
    SPACE_INDENT . 'location ~ \.php' . PHP_EOL .
    OPENING_BRACKET .
    SPACE_INDENT_2 . 'include snippets/fastcgi-php.conf;' . PHP_EOL .
    SPACE_INDENT_2 . 'fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;' . PHP_EOL .
    PHP_EOL .
    SPACE_INDENT_2 . 'fastcgi_param APP_ENV ' . GEN_SERVER_CONFIG_ENVIRONMENT .';' . PHP_EOL .
    SPACE_INDENT_2 . 'fastcgi_param TEST_LOGIN yourLogin;' . PHP_EOL .
    SPACE_INDENT_2 . 'fastcgi_param TEST_PASSWORD yourPassword;' . PHP_EOL .
    SPACE_INDENT . '}';
}

$content = handlesHTTPSRedirection() .
  PHP_EOL .
  'server' . PHP_EOL .
  '{' . PHP_EOL .
  SPACE_INDENT . 'listen 443 ssl http2;' . PHP_EOL .
  SPACE_INDENT . 'listen [::]:443 ssl http2;' . PHP_EOL .
  SPACE_INDENT . 'set $rootPath ' . AllConfig::$deployment[GEN_SERVER_CONFIG_FOLDER_KEY] . ';' . PHP_EOL .
  SPACE_INDENT . 'index index' . (GEN_SERVER_CONFIG_ENVIRONMENT === DEV ? 'Dev' : '') . '.php;' . PHP_EOL .
  PHP_EOL .
  SPACE_INDENT . '# Updates the server_name if needed' . PHP_EOL .
  SPACE_INDENT . 'server_name ' . GEN_SERVER_CONFIG_SERVER_NAME . ';' . PHP_EOL .
  SPACE_INDENT . 'error_log /var/log/nginx/' . GEN_SERVER_CONFIG_SERVER_NAME . '/error.log error;' . PHP_EOL .
  SPACE_INDENT . 'access_log /var/log/nginx/' . GEN_SERVER_CONFIG_SERVER_NAME . '/access.log;' . PHP_EOL .
  PHP_EOL .
  SPACE_INDENT . 'root $rootPath/web;' . PHP_EOL .
  PHP_EOL .
  SPACE_INDENT . 'ssl_certificate ' . AllConfig::$deployment[GEN_SERVER_CONFIG_FOLDER_KEY] .
  'tmp/certs/server_crt.pem;' . PHP_EOL .
  SPACE_INDENT . 'ssl_certificate_key ' . AllConfig::$deployment[GEN_SERVER_CONFIG_FOLDER_KEY] .
  'tmp/certs/server_key.pem;' . PHP_EOL .
  PHP_EOL .
  SPACE_INDENT . 'expires $expires;' . PHP_EOL .
  SPACE_INDENT . '#HSTS' . PHP_EOL .
  SPACE_INDENT . 'add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload";' . PHP_EOL .
  PHP_EOL .
  SPACE_INDENT . '# XSS protections' . PHP_EOL .
  SPACE_INDENT . 'add_header X-Content-Type-Options "nosniff";' . PHP_EOL .
  SPACE_INDENT . 'add_header X-XSS-Protection "1; mode=block";' . PHP_EOL .
  PHP_EOL .
  SPACE_INDENT . '# Avoid showing server version ...' . PHP_EOL .
  SPACE_INDENT . 'server_tokens off;' . PHP_EOL .
  SPACE_INDENT . 'more_set_headers \'Server: Welcome on my site!\';' . PHP_EOL .
  PHP_EOL .
  SPACE_INDENT . '# This header allows you to control whether or not you leak information about your users\' behavior to third parties' .
  PHP_EOL .
  SPACE_INDENT . 'add_header Referrer-Policy same-origin always;' . PHP_EOL .
  PHP_EOL .
  SPACE_INDENT . '# Prevents hotlinking (others that steal our bandwidth and assets).' . PHP_EOL .
  SPACE_INDENT . 'valid_referers none blocked ~.google. ~.bing. ~.yahoo. ' .
  AllConfig::$deployment[GEN_SERVER_CONFIG_DOMAIN_NAME_KEY] . ' *.' .
  AllConfig::$deployment[GEN_SERVER_CONFIG_DOMAIN_NAME_KEY] .
  ' localhost;' . PHP_EOL .
  PHP_EOL .
  SPACE_INDENT . 'if ($uri ~ "^/$")' . PHP_EOL .
  SPACE_INDENT . '{' . PHP_EOL .
  SPACE_INDENT_2 . 'set $invalid_referer "";' . PHP_EOL .
  SPACE_INDENT . '}' . PHP_EOL .
  PHP_EOL .
  SPACE_INDENT . 'if ($uri ~ "^/(robots.txt|sitemap.xml)$")' . PHP_EOL .
  SPACE_INDENT . '{' . PHP_EOL .
  SPACE_INDENT_2 . 'set $invalid_referer "";' . PHP_EOL .
  SPACE_INDENT . '}' . PHP_EOL .
  PHP_EOL .
  SPACE_INDENT . 'if ($invalid_referer)' . PHP_EOL .
  SPACE_INDENT . '{' . PHP_EOL .
  SPACE_INDENT_2 . 'return 403;' . PHP_EOL .
  SPACE_INDENT . '}' . PHP_EOL .
  PHP_EOL .
  SPACE_INDENT . '# Forces static compression (for already compressed files)' . PHP_EOL .
  SPACE_INDENT . 'brotli on;' . PHP_EOL .
  SPACE_INDENT . 'brotli_comp_level 7;' . PHP_EOL .
  SPACE_INDENT . 'brotli_static always;' . PHP_EOL .
  SPACE_INDENT . 'gzip off;' . PHP_EOL .
  PHP_EOL .
  SPACE_INDENT . '# Blocking any page that don\'t send a referrer via if conditions in the next \'location\' blocks' .
  PHP_EOL .
  PHP_EOL .
  SPACE_INDENT . '# Handling the compressed web manifest' . PHP_EOL .
  SPACE_INDENT . 'location ~ /manifest\.br' . PHP_EOL .
  OPENING_BRACKET .
  CHECK_HTTP_REFERER_2 . PHP_EOL .
  LABEL_TYPES_2 .
  OPENING_BRACKET_2 .
  SPACE_INDENT_3 . 'application/manifest+json br;' . PHP_EOL .
  ENDING_BRACKET_2 .
  PHP_EOL .
  SPACE_INDENT_2 . 'brotli_types application/manifest+json;' . PHP_EOL .
  COMPRESSED_HEADER_2 .
  addingSecurityAdjustments() .
  ENDING_BRACKET .

  (GEN_SERVER_CONFIG_ENVIRONMENT === DEV
  ? PHP_EOL .
    handleCompressedAsset() .
    PHP_EOL .
    handleCompressedAsset('js') .
    PHP_EOL .
    SPACE_INDENT . '# Handling CSS, JS ...and TS for source maps(project and vendor)' . PHP_EOL .
    SPACE_INDENT . 'location ~ /(bundles|src|vendor)/.*\.(css|js|ts)$' . PHP_EOL .
    OPENING_BRACKET .
    CHECK_HTTP_REFERER_2 . PHP_EOL .
    SPACE_INDENT_2 . OTRA_LABEL_ROOT_PATH . PHP_EOL .
    ENDING_BRACKET .
    PHP_EOL .
    SPACE_INDENT . '# Handling CSS and JS source maps (project and vendor)' . PHP_EOL .
    SPACE_INDENT . 'location ~ /(bundles|src|vendor)/.*\.(css|js)\.map$' . PHP_EOL .
    OPENING_BRACKET .
    LABEL_TYPES_2 .
    OPENING_BRACKET_2 .
    LABEL_APPLICATION_JSON_3 .
    ENDING_BRACKET_2 .
    PHP_EOL .
    SPACE_INDENT_2 . OTRA_LABEL_ROOT_PATH . PHP_EOL .
    ENDING_BRACKET .
    PHP_EOL .
    SPACE_INDENT . '# Handling PWA js mapping' . PHP_EOL .
    SPACE_INDENT . 'location ~ /sw\.js\.map$' . PHP_EOL .
    OPENING_BRACKET .
    LABEL_TYPES_2 .
    OPENING_BRACKET_2 .
    LABEL_APPLICATION_JSON_3 .
    ENDING_BRACKET_2 .
    ENDING_BRACKET
  : PHP_EOL .
    handleCompressedAsset() .
    PHP_EOL .
    SPACE_INDENT . '# For local testing purpose only' . PHP_EOL .
    SPACE_INDENT . 'location ~ /vendor/.*\.css$' . PHP_EOL .
    OPENING_BRACKET .
    CHECK_HTTP_REFERER_2 .
    PHP_EOL .
    SPACE_INDENT_2 . 'brotli_types text/css;' . PHP_EOL .
    SPACE_INDENT_2 . 'root $rootPath;' . PHP_EOL .
    ENDING_BRACKET .
    PHP_EOL .
    handleCompressedAsset('js') .
    PHP_EOL .
    handleCompressedAsset('tpl')
  ) .
  PHP_EOL .
  handleWebFolderAssets() .
  PHP_EOL .
  handleRewriting() . PHP_EOL .
  '}' . PHP_EOL;

// Generating the main configuration file.
if (file_put_contents($fileName, $content) === false)
{
  echo CLI_ERROR . 'There was a problem when saving the Nginx configuration in the file ' . CLI_INFO_HIGHLIGHT .
    $fileName . CLI_ERROR . '.' . END_COLOR . PHP_EOL;
  throw new OtraException(code: 1, exit: true);
}

$dotPosition = mb_strrpos($fileName, '.');
$cacheHandlingFilename = mb_substr($fileName, 0, $dotPosition) . '_cache' . mb_substr($fileName, $dotPosition);

if (file_put_contents(
  $cacheHandlingFilename,
  'map $sent_http_content_type $expires {' . PHP_EOL .
  SPACE_INDENT . 'default                 off;' . PHP_EOL .
  SPACE_INDENT . 'text/html               epoch;' . PHP_EOL .
  SPACE_INDENT . 'text/css                max;' . PHP_EOL .
  SPACE_INDENT . 'application/javascript  max;' . PHP_EOL .
  SPACE_INDENT . 'text/javascript         max;' . PHP_EOL .
  SPACE_INDENT . '~image/                 max;' . PHP_EOL .
  SPACE_INDENT . 'application/json        max;' . PHP_EOL .
  SPACE_INDENT . 'font/woff2              max;' . PHP_EOL .
  '}' . PHP_EOL
) === false)
{
  echo CLI_ERROR . 'There was a problem when saving the Nginx cache configuration in the file ' . CLI_INFO_HIGHLIGHT .
    $cacheHandlingFilename . CLI_ERROR . '.' . END_COLOR . PHP_EOL;
  throw new OtraException(code: 1, exit: true);
}

echo CLI_BASE . 'Nginx ' . (GEN_SERVER_CONFIG_ENVIRONMENT === DEV ? 'development' : 'production') .
  ' server configuration generated in ' . CLI_INFO_HIGHLIGHT . $fileName . CLI_BASE .
  ' and the cache configuration in ' . CLI_INFO_HIGHLIGHT . $cacheHandlingFilename . CLI_BASE . '.' . PHP_EOL .
  'Do not forget to include the cache file in your main server configuration file!' . END_COLOR . PHP_EOL;
return 0;
