<?php
declare(strict_types=1);
namespace otra\console\deployment\genBootstrap;
/**
 * @author Lionel Péramo
 * @package otra\console\deployment
 */

use otra\console\TasksManager;
use const otra\console\{CLI_INFO, CLI_INFO_HIGHLIGHT};

return [
  'Launch the genClassMap command and generates a file that contains all the necessary php files.',
  [
    'genclassmap' => 'If set to ' . CLI_INFO_HIGHLIGHT . '0' . CLI_INFO . 
      ', it prevents the generation/override of the class mapping file.',
    'verbose' => 'If set to ' . CLI_INFO_HIGHLIGHT . '1' . CLI_INFO . 
      ', we print all the main warnings when the task fails. Put ' . CLI_INFO_HIGHLIGHT . '2' . CLI_INFO . 
      ' to get every warning. Defaults to ' . CLI_INFO_HIGHLIGHT . '0' . CLI_INFO . '.',
    'lint' => 'Checks for syntax errors. ' . CLI_INFO_HIGHLIGHT . '0' . CLI_INFO . ' disabled, ' . CLI_INFO_HIGHLIGHT . 
      '1' . CLI_INFO . ' enabled (defaults to ' . CLI_INFO_HIGHLIGHT . '0' . CLI_INFO . ')',
    'route' => 'The route for which you want to generate the micro bootstrap. Defaults to ' . CLI_INFO_HIGHLIGHT . 
      '_all' . CLI_INFO . '.',
    'environment' => 'What environment are we aiming for? Defaults to ' . CLI_INFO_HIGHLIGHT . 'prod' . CLI_INFO . '.'
  ],
  [
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER
  ],
  'Deployment'
];
