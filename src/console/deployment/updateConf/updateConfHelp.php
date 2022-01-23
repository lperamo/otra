<?php
declare(strict_types=1);

namespace otra\console\deployment\updateConf;

use const otra\cache\php\CONSOLE_PATH;
use const otra\console\STRING_PAD_FOR_OPTION_FORMATTING;
use const otra\src\console\deployment\updateConf\
{UPDATE_CONF_MASK_ALL,
  UPDATE_CONF_MASK_ALL_CONFIG,
  UPDATE_CONF_MASK_FIXTURES,
  UPDATE_CONF_MASK_ROUTES,
  UPDATE_CONF_MASK_SCHEMA,
  UPDATE_CONF_MASK_SECURITIES};

// can hang on `init` task, if we put `require`
require_once CONSOLE_PATH . 'deployment/updateConf/updateConfConstants.php';

/**
 * @author Lionel Péramo
 * @package otra\console\deployment
 */

return [
  'Updates the files related to bundles and routes : schemas, routes, securities.',
  [
    'mask' => UPDATE_CONF_MASK_ALL_CONFIG . ' => Config.php files' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . UPDATE_CONF_MASK_ROUTES . ' => routes' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . UPDATE_CONF_MASK_SECURITIES . ' => securities' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . UPDATE_CONF_MASK_SCHEMA . ' => schema.yml' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . UPDATE_CONF_MASK_FIXTURES . ' => fixtures' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . UPDATE_CONF_MASK_ALL . ' => All (Default)',
    'route' => 'To update only security files related to one specific route'
  ],
  ['optional', 'optional'],
  'Deployment'
];
