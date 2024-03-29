<?php
declare(strict_types=1);

namespace otra\tools;

use JsonException;
use otra\OtraException;
use const otra\cache\php\{BASE_PATH, CORE_PATH, OTRA_PROJECT};
use const otra\console\CLI_ERROR;
use const otra\console\CLI_INFO;

if (!function_exists(__NAMESPACE__ . '\\getOtraCommitNumber'))
{
  /**
   * @param bool  $console Do we have to use console colors?
   * @param false $short   Do we show the entire commit number or the short version?
   *
   * @throws JsonException|OtraException
   * @return string
   */
  function getOtraCommitNumber(bool $console = false, bool $short = false) : string
  {
    if (OTRA_PROJECT)
    {
      $installedComposerPackages = json_decode(
        file_get_contents(BASE_PATH . 'vendor/composer/installed.json'),
        true,
        512,
        JSON_THROW_ON_ERROR
      )['packages'];
      $commitNumber = $installedComposerPackages[array_search(
        'otra/otra',
        array_column($installedComposerPackages, 'name')
      )]['source']['reference'];

      return $short ? substr($commitNumber, 0, 8) : $commitNumber;
    } elseif (php_sapi_name() !== 'cli') {
      return 'Use the command line<br>git rev-parse ' . ($short ? '--short=8' : '') . ' HEAD<br>to know it.';
    }

    require CORE_PATH . 'tools/cli.php';

    return cliCommand(
      'git rev-parse ' . ($short ? '--short=8' : '') . ' HEAD'
    )[OTRA_CLI_OUTPUT];
  }
}
