<?php
/**
 * Server configuration generation task
 *
 * @author Lionel Péramo
 * @package otra\console\deployment
 */
declare(strict_types=1);
namespace otra\console;

use config\AllConfig;
use otra\OtraException;

define('GEN_SERVER_CONFIG_ARG_FILE', 2);
define('GEN_SERVER_CONFIG_ARG_ENVIRONMENT', 3);
define('GEN_SERVER_CONFIG_ARG_SERVER_TECHNOLOGY', 4);

$fileName = $argv[GEN_SERVER_CONFIG_ARG_FILE];

define(
  'GEN_SERVER_CONFIG_ENVIRONMENT',
  (isset($argv[GEN_SERVER_CONFIG_ARG_ENVIRONMENT]))
  ? $argv[GEN_SERVER_CONFIG_ARG_ENVIRONMENT]
  : 'dev'
);

define(
  'GEN_SERVER_CONFIG_SERVER_TECHNOLOGY',
  (isset($argv[GEN_SERVER_CONFIG_ARG_SERVER_TECHNOLOGY]))
  ? $argv[GEN_SERVER_CONFIG_ARG_SERVER_TECHNOLOGY]
  : 'nginx'
);

if (!isset(AllConfig::$deployment))
{
  echo CLI_RED . 'There is no deployment configuration so we cannot know which server name to use.' . PHP_EOL;
  throw new OtraException('', 1, '', NULL, [], true);
}

define('GEN_SERVER_CONFIG_DOMAIN_NAME_KEY', 'domainName');

if (!isset(AllConfig::$deployment[GEN_SERVER_CONFIG_DOMAIN_NAME_KEY]))
{
  echo CLI_LIGHT_CYAN . GEN_SERVER_CONFIG_DOMAIN_NAME_KEY . CLI_RED .
    ' is not defined in the deployment configuration so we cannot know which server name to use.' . PHP_EOL;
  throw new OtraException('', 1, '', NULL, [], true);
}

define('GEN_SERVER_CONFIG_FOLDER_KEY', 'folder');

if (!isset(AllConfig::$deployment[GEN_SERVER_CONFIG_FOLDER_KEY]))
{
  echo CLI_LIGHT_CYAN . GEN_SERVER_CONFIG_FOLDER_KEY . CLI_RED .
    ' is not defined in the deployment configuration so we cannot know which server name to use.' . PHP_EOL;
  throw new OtraException('', 1, '', NULL, [], true);
}

define('GEN_SERVER_CONFIG_STR_PAD', 12);
define('SPACE_INDENT_2', SPACE_INDENT . SPACE_INDENT);
define('SPACE_INDENT_3', SPACE_INDENT_2 . SPACE_INDENT);
define(
  'GEN_SERVER_CONFIG_SERVER_NAME',
  GEN_SERVER_CONFIG_ENVIRONMENT . '.' . AllConfig::$deployment[GEN_SERVER_CONFIG_DOMAIN_NAME_KEY]
);

require CONSOLE_PATH . 'deployment/genServerConfig/' . GEN_SERVER_CONFIG_SERVER_TECHNOLOGY . 'ServerConfig.php';

