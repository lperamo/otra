<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\deployment
 */

use config\AllConfig;
use JetBrains\PhpStorm\Pure;

/** @var string $fileName */

define('OTRA_LABEL_RETURN_403', 'return 403;');
define('OTRA_LABEL_TYPES', 'types');
define('OTRA_LABEL_ROOT_PATH', 'root $rootPath;');

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
    SPACE_INDENT . str_pad('return', GEN_SERVER_CONFIG_STR_PAD) . '301 https://$server_name$request_uri; #Redirection' . PHP_EOL .
    '}' . PHP_EOL;
}

/**
 * Handles listening on SSL ports.
 * Sets the path to the SSL certificate.
 * Sets the path to the logs folders.
 * And some additional basic configuration...
 *
 * @return string
 */
function handleBasicConfiguration() : string
{
  return SPACE_INDENT . 'listen 443 ssl http2;' . PHP_EOL .
    SPACE_INDENT . 'listen [::]:443 ssl http2;' . PHP_EOL .
    SPACE_INDENT . 'set $rootPath ' . AllConfig::$deployment[GEN_SERVER_CONFIG_FOLDER_KEY] . ';' . PHP_EOL .
    SPACE_INDENT . 'index ' . (GEN_SERVER_CONFIG_ENVIRONMENT === 'dev' ? 'indexDev.php;' : 'index.php;') . PHP_EOL .
    PHP_EOL .
    SPACE_INDENT . '# Updates the server_name if needed' . PHP_EOL .
    SPACE_INDENT . 'server_name ' . GEN_SERVER_CONFIG_SERVER_NAME . ';' . PHP_EOL .
    SPACE_INDENT . 'error_log /var/log/nginx/' . GEN_SERVER_CONFIG_SERVER_NAME . '/error.log error;' . PHP_EOL .
    SPACE_INDENT . 'access_log /var/log/nginx/' . GEN_SERVER_CONFIG_SERVER_NAME . '/access.log;' . PHP_EOL .
    PHP_EOL .
    SPACE_INDENT . 'root $rootPath/web;' . PHP_EOL;
}

/**
 * Enables ssl.
 * Sets the certificates.
 * Improves security.
 * Protects against clickjacking, XSS attacks, hotlinking and some other things.
 *
 * @return string
 */
function handleSecurity(): string
{
  return SPACE_INDENT . 'ssl_certificate ' . AllConfig::$deployment[GEN_SERVER_CONFIG_FOLDER_KEY] .
    'tmp/certs/server_crt.pem;' . PHP_EOL .
    SPACE_INDENT . 'ssl_certificate_key ' . AllConfig::$deployment[GEN_SERVER_CONFIG_FOLDER_KEY] .
    'tmp/certs/server_key.pem;' . PHP_EOL .
    PHP_EOL .
    SPACE_INDENT . '#HSTS' . PHP_EOL .
    SPACE_INDENT . 'add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload";' . PHP_EOL .
    PHP_EOL .
    SPACE_INDENT . '# Clickjacking protection' . PHP_EOL .
    SPACE_INDENT . 'add_header X-Frame-Options "DENY";' . PHP_EOL .
    PHP_EOL .
    SPACE_INDENT . '# XSS protections' . PHP_EOL .
    SPACE_INDENT . 'add_header X-Content-Type-Options "nosniff";' . PHP_EOL .
    SPACE_INDENT . 'add_header X-XSS-Protection "1; mode=block";' . PHP_EOL .
    PHP_EOL .
    SPACE_INDENT . '# Avoid showing server version ...' . PHP_EOL .
    SPACE_INDENT . 'server_tokens off;' . PHP_EOL .
    PHP_EOL .
    SPACE_INDENT . '# Sending always referrer if it is secure' . PHP_EOL .
    SPACE_INDENT . 'add_header Referrer-Policy same-origin always;' . PHP_EOL .
    PHP_EOL .
    SPACE_INDENT . '# Prevents hotlinking (others that steal our bandwidth and assets).' . PHP_EOL .
    SPACE_INDENT . 'valid_referers none blocked ~.google. ~.bing. ~.yahoo. ' .
    AllConfig::$deployment[GEN_SERVER_CONFIG_DOMAIN_NAME_KEY] . ' *.' .
    AllConfig::$deployment[GEN_SERVER_CONFIG_DOMAIN_NAME_KEY] .
    ' localhost;' . PHP_EOL .
    PHP_EOL .
    SPACE_INDENT . 'if ($invalid_referer)' . PHP_EOL .
    SPACE_INDENT . '{' . PHP_EOL .
    SPACE_INDENT . SPACE_INDENT . OTRA_LABEL_RETURN_403 . PHP_EOL .
    SPACE_INDENT . '}' . PHP_EOL;
}

/**
 * It forces to use already gzipped resources instead of classic/decompressed ones.
 *
 * @return string
 */
function handleStaticCompression(): string
{
  return SPACE_INDENT . '# Forces static compression (for already gzipped files)' . PHP_EOL .
    SPACE_INDENT . 'gzip off;' . PHP_EOL .
    SPACE_INDENT . 'gzip_static always;' . PHP_EOL;
}

/**
 * If the HTTP referer is empty, we send a 403.
 *
 * @return string
 */
function checkHttpReferer() : string
{
  return SPACE_INDENT_2 . 'if ($http_referer = "")' . PHP_EOL .
    SPACE_INDENT_2 . '{' . PHP_EOL .
    SPACE_INDENT_3 . OTRA_LABEL_RETURN_403 . PHP_EOL .
    SPACE_INDENT_2 . '}' . PHP_EOL;
}

/**
 * Handles the gzipped web manifest.
 *
 * @return string
 */
#[Pure] function handleManifest(): string
{
  return SPACE_INDENT . '# Handling the gzipped web manifest' . PHP_EOL .
    SPACE_INDENT . 'location ~ /manifest\.gz' . PHP_EOL .
    SPACE_INDENT . '{' . PHP_EOL .
    checkHttpReferer() . PHP_EOL .
    SPACE_INDENT_2 . OTRA_LABEL_TYPES . PHP_EOL .
    SPACE_INDENT_2 . '{' . PHP_EOL .
    SPACE_INDENT_3 . 'application/manifest+json gz;' . PHP_EOL .
    SPACE_INDENT_2 . '}' . PHP_EOL .
    PHP_EOL .
    SPACE_INDENT_2 . 'gzip_types application/manifest+json;' . PHP_EOL .
    SPACE_INDENT_2 . 'add_header Content-Encoding gzip;' . PHP_EOL .
    SPACE_INDENT . '}' . PHP_EOL;
}

/**
 * @param string $assetType
 *
 * @return string
 */
function handleGzippedAsset(string $assetType = 'css'): string
{
  $mimeType = [
    'css' => 'text/css',
    'js' => 'application/javascript',
    'tpl' => 'text/html'
  ][$assetType];

  return SPACE_INDENT . '# Handling ' . strtoupper($assetType) . PHP_EOL .
    SPACE_INDENT . 'location ~ /cache/' . $assetType . '/.*\.gz$' . PHP_EOL .
    SPACE_INDENT . '{' . PHP_EOL .
    checkHttpReferer() . PHP_EOL .
    SPACE_INDENT_2 . OTRA_LABEL_TYPES . PHP_EOL .
    SPACE_INDENT_2 . '{' . PHP_EOL .
    SPACE_INDENT_3 . $mimeType . ' gz;' . PHP_EOL .
    SPACE_INDENT_2 . '}' . PHP_EOL .
    PHP_EOL .
    SPACE_INDENT_2 . 'gzip_types ' . $mimeType . ';' . PHP_EOL .
    SPACE_INDENT_2 . 'add_header Content-Encoding gzip;' . PHP_EOL .
    PHP_EOL .
    SPACE_INDENT_2 . OTRA_LABEL_ROOT_PATH . PHP_EOL .
    SPACE_INDENT . '}' . PHP_EOL;
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
    SPACE_INDENT . '{' . PHP_EOL .
    checkHttpReferer() .
    SPACE_INDENT . '}' . PHP_EOL .
    PHP_EOL .
    SPACE_INDENT . '# Handling images and fonts' . PHP_EOL .
    SPACE_INDENT . 'location ~ /.*\.(ico|jpe?g|png|svg|webp|woff2)$' . PHP_EOL .
    SPACE_INDENT . '{' . PHP_EOL .
    SPACE_INDENT_2 .
    '# Workaround, Firefox does not send referrer for favicons so we send always the images when Firefox is used' .
    PHP_EOL .
    PHP_EOL .
    SPACE_INDENT_2 . '# Handles the case of "not favicon images" when using Firefox' . PHP_EOL .
    SPACE_INDENT_2 . 'set $referer $http_referer;' . PHP_EOL.
    PHP_EOL .
    SPACE_INDENT_2 . 'if ($uri ~ "^/(favicon|apple-touch).*\.png$")' . PHP_EOL .
    SPACE_INDENT_2 . '{' . PHP_EOL .
    SPACE_INDENT_3 . 'set $referer https://$server_name/;' . PHP_EOL .
    SPACE_INDENT_2 . '}' . PHP_EOL .
    PHP_EOL .
    SPACE_INDENT_2 . 'if ($http_user_agent !~ ".*Firefox.*")' . PHP_EOL .
    SPACE_INDENT_2 . '{' . PHP_EOL .
    SPACE_INDENT_3 . 'set $referer $http_referer;' . PHP_EOL .
    SPACE_INDENT_2 . '}' . PHP_EOL .
    PHP_EOL .
    SPACE_INDENT_2 . 'if ($referer = "")' . PHP_EOL .
    SPACE_INDENT_2 . '{' . PHP_EOL .
    SPACE_INDENT_3 . OTRA_LABEL_RETURN_403 . PHP_EOL .
    SPACE_INDENT_2 . '}' . PHP_EOL .
    PHP_EOL .
    SPACE_INDENT_2 . '# Handle vendor images' . PHP_EOL .
    SPACE_INDENT_2 . 'if ($uri ~ "^/vendor.*$")' . PHP_EOL .
    SPACE_INDENT_2 . '{' . PHP_EOL .
    SPACE_INDENT_3 . OTRA_LABEL_ROOT_PATH . PHP_EOL .
    SPACE_INDENT_2 . '}' . PHP_EOL .
    SPACE_INDENT . '}' . PHP_EOL;
}

/**
 * Handle PHP requests
 *
 * @return string
 */
function handleRewriting() : string
{
  return SPACE_INDENT . '# Handles rewriting' . PHP_EOL .
    SPACE_INDENT . 'location /' . PHP_EOL .
    SPACE_INDENT . '{' . PHP_EOL .
    SPACE_INDENT_2 . 'rewrite ^ /index' . (GEN_SERVER_CONFIG_ENVIRONMENT === 'dev' ? 'Dev' : '') . '.php last;' . PHP_EOL .
    SPACE_INDENT . '}' . PHP_EOL .
    PHP_EOL .
    SPACE_INDENT . 'location ~ \.php' . PHP_EOL .
    SPACE_INDENT . '{' . PHP_EOL .
    SPACE_INDENT_2 . 'include snippets/fastcgi-php.conf;' . PHP_EOL .
    SPACE_INDENT_2 . 'fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;' . PHP_EOL .
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
  handleBasicConfiguration() .
  PHP_EOL .
  handleSecurity() .
  PHP_EOL .
  handleStaticCompression() .
  PHP_EOL .
  SPACE_INDENT . '# Blocking any page that don\'t send a referrer via if conditions in the next \'location\' blocks' .
  PHP_EOL .
  PHP_EOL .
  handleManifest() .

  (GEN_SERVER_CONFIG_ENVIRONMENT === 'dev'
  ? PHP_EOL .
    SPACE_INDENT . '# Handling CSS and JS (project and vendor)' . PHP_EOL .
    SPACE_INDENT . 'location ~ /(bundles|vendor)/.*\.(css|js)$' . PHP_EOL .
    SPACE_INDENT . '{' . PHP_EOL .
    checkHttpReferer() . PHP_EOL .
    SPACE_INDENT_2 . OTRA_LABEL_ROOT_PATH . PHP_EOL .
    SPACE_INDENT . '}' . PHP_EOL .
    PHP_EOL .
    SPACE_INDENT . '# Handling CSS and JS source maps (project and vendor)' . PHP_EOL .
    SPACE_INDENT . 'location ~ /(bundles|vendor)/.*\.(css|js)\.map$' . PHP_EOL .
    SPACE_INDENT . '{' . PHP_EOL .
    SPACE_INDENT_2 . OTRA_LABEL_TYPES . PHP_EOL .
    SPACE_INDENT_2 . '{' . PHP_EOL .
    SPACE_INDENT_3 . 'application/json map;' . PHP_EOL .
    SPACE_INDENT_2 . '}' . PHP_EOL .
    PHP_EOL .
    SPACE_INDENT_2 . OTRA_LABEL_ROOT_PATH . PHP_EOL .
    SPACE_INDENT . '}' . PHP_EOL
  : PHP_EOL .
    handleGzippedAsset() .
    PHP_EOL .
    SPACE_INDENT . '# For local testing purpose only' . PHP_EOL .
    SPACE_INDENT . 'location ~ /vendor/.*\.css$' . PHP_EOL .
    SPACE_INDENT . '{' . PHP_EOL .
    SPACE_INDENT_2 . 'if ($http_referer = "")' . PHP_EOL .
    SPACE_INDENT_2 . '{' . PHP_EOL .
    SPACE_INDENT_3 . 'return 403;' . PHP_EOL .
    SPACE_INDENT_2 . '}' . PHP_EOL .
    PHP_EOL .
    SPACE_INDENT_2 . 'gzip_types text/css;' . PHP_EOL .
    SPACE_INDENT_2 . 'root $rootPath;' . PHP_EOL .
    SPACE_INDENT . '}' . PHP_EOL .
    PHP_EOL .
    handleGzippedAsset('js') .
    PHP_EOL .
    handleGzippedAsset('tpl')
  ) .
  PHP_EOL .
  handleWebFolderAssets() .
  PHP_EOL .
  handleRewriting() . PHP_EOL .
  '}' . PHP_EOL;

file_put_contents($fileName, $content);

echo 'Nginx ' . (GEN_SERVER_CONFIG_ENVIRONMENT === 'dev' ? 'development' : 'production') .
  ' server configuration generated in ' . CLI_LIGHT_CYAN . $fileName . END_COLOR . '.' . PHP_EOL;
return 0;
