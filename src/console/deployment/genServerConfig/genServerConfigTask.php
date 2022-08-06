<?php
/**
 * Server configuration generation task
 *
 * @author Lionel Péramo
 * @package otra\console\deployment
 */
declare(strict_types=1);
namespace otra\console\deployment\genServerConfig;

use otra\config\AllConfig;
use otra\OtraException;
use const otra\cache\php\{BUNDLES_PATH, CONSOLE_PATH, DEV, SPACE_INDENT};
use const otra\console\
{CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR};

const
  GEN_SERVER_CONFIG_ARG_FILE = 2,
  GEN_SERVER_CONFIG_ARG_ENVIRONMENT = 3,
  GEN_SERVER_CONFIG_ARG_SERVER_TECHNOLOGY = 4,
  GEN_SERVER_CONFIG_DOMAIN_NAME_KEY = 'domainName',
  GEN_SERVER_CONFIG_FOLDER_KEY = 'folder',
  GEN_SERVER_CONFIG_STR_PAD = 12,
  SPACE_INDENT_2 = SPACE_INDENT . SPACE_INDENT,
  SPACE_INDENT_3 = SPACE_INDENT_2 . SPACE_INDENT;

/**
 *
 * @throws OtraException
 * @return void
 */
function genServerConfig(array $argumentsVector) : void
{
  $fileName = $argumentsVector[GEN_SERVER_CONFIG_ARG_FILE];

  define(
    __NAMESPACE__ . '\\GEN_SERVER_CONFIG_ENVIRONMENT',
    $argumentsVector[GEN_SERVER_CONFIG_ARG_ENVIRONMENT] ?? DEV
  );

  define(
    __NAMESPACE__ . '\\GEN_SERVER_CONFIG_SERVER_TECHNOLOGY',
    $argumentsVector[GEN_SERVER_CONFIG_ARG_SERVER_TECHNOLOGY] ?? 'nginx'
  );

  if (!isset(AllConfig::$deployment))
  {
    echo CLI_ERROR, 'There is no deployment configuration so we cannot know which server name to use.', END_COLOR,
      PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }

  if (!isset(AllConfig::$deployment[GEN_SERVER_CONFIG_DOMAIN_NAME_KEY]))
  {
    echo CLI_INFO_HIGHLIGHT, GEN_SERVER_CONFIG_DOMAIN_NAME_KEY, CLI_ERROR,
      ' is not defined in the deployment configuration so we cannot know which domain name to use.', END_COLOR, PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }

  if (!isset(AllConfig::$deployment[GEN_SERVER_CONFIG_FOLDER_KEY]))
  {
    echo CLI_INFO_HIGHLIGHT, GEN_SERVER_CONFIG_FOLDER_KEY, CLI_ERROR,
      ' is not defined in the deployment configuration so we cannot know which server name to use.', END_COLOR, PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }

  if (!file_exists(BUNDLES_PATH . 'config/Routes.php'))
  {
    echo CLI_ERROR, 'No routes are defined in the project!', END_COLOR, PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }

  define(
    __NAMESPACE__ . '\\GEN_SERVER_CONFIG_SERVER_NAME',
    GEN_SERVER_CONFIG_ENVIRONMENT . '.' . AllConfig::$deployment[GEN_SERVER_CONFIG_DOMAIN_NAME_KEY]
  );

  require CONSOLE_PATH . 'deployment/genServerConfig/' . GEN_SERVER_CONFIG_SERVER_TECHNOLOGY . 'ServerConfig.php';
}
