<?php
declare(strict_types=1);
namespace otra\console\deployment\genAssets;
/**
 * @author Lionel Péramo
 * @package otra\console\deployment
 */

use otra\console\TasksManager;

return [
  'Generates one css file and one js file that contain respectively all the minified css files and all the obfuscated minified js files. Gzips the SVGs.',
  [
    'mask' => '1 => templates' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '2 => CSS' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '4 => JS' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '8 => JSON manifest' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '16 => SVG' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '31 => all (default)',
    'js_level_compilation' => 'Optimization level for Google Closure Compiler' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '0 for WHITESPACE_ONLY' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '1 for SIMPLE_OPTIMIZATIONS (default)' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '2 for ADVANCED_OPTIMIZATIONS',
    'route' => 'The route for which you want to generate resources.'
  ],
  [
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER
  ],
  'Deployment'
];
