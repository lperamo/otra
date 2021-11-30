<?php
declare(strict_types=1);
namespace otra\src\tools\debug;

use otra\OtraException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use const otra\cache\php\CORE_PATH;
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR};
use function otra\tools\files\returnLegiblePath;

/**
 * @param string $yamlContent
 * @param string $yamlFile
 *
 * @throws OtraException
 * @return array
 */
function validateYaml(string $yamlContent, string $yamlFile): array
{
  try
  {
    return Yaml::parse($yamlContent);
  } catch(ParseException $exception)
  {
    require CORE_PATH . 'tools/files/returnLegiblePath.php';
    echo CLI_ERROR, 'Problem in ', CLI_INFO_HIGHLIGHT, returnLegiblePath($yamlFile), CLI_ERROR, ' :', PHP_EOL,
      $exception->getMessage(), END_COLOR, PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }
}
