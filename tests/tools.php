<?php
declare(strict_types=1);

namespace otra\tests\tools;

use otra\console\TasksManager;
use const otra\console\CLI_GRAY;
use const otra\console\CLI_INFO;
use const otra\console\CLI_INFO_HIGHLIGHT;

/**
 * @param string $parameter
 * @param string $description
 * @param string $requiredOrOptional 'required' or 'optional'
 *
 * @return string
 */
function taskParameter(string $parameter, string $description, string $requiredOrOptional): string
{
  return CLI_INFO_HIGHLIGHT . '   + ' .
    str_pad($parameter, TasksManager::PAD_LENGTH_FOR_TASK_OPTION_FORMATTING) . CLI_GRAY . ': ' .
    CLI_INFO_HIGHLIGHT . '(' . $requiredOrOptional . ') ' . CLI_INFO . $description . PHP_EOL;
}
