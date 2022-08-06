<?php
/**
 * @author  Lionel Péramo
 * @package otra\tools\debug
 */

declare(strict_types=1);

use otra\config\AllConfig;
use const otra\cache\php\CORE_PATH;

if (!function_exists(__NAMESPACE__ . '\\dump'))
{
  /**
   * A shortcut to the 'paramDump' function that will use the user configuration.
   */
  function dump(mixed ... $params) : void
  {
    paramDump(null, ...$params);
  }

  /**
   * A nice dump function that takes as many parameters as we want to put.
   * The output is conditioned by the options passed in parameters
   *
   * @param ?int[] $options [0 => Affects the amount of array children and object's properties shown
   *                         1 => Affects the maximum string length shown
   *                         2 => Affects the array and object's depths shown]
   */
  function paramDump(?array $options = [], mixed ... $params) : void
  {
    if (!defined('OTRA_DUMP_FINAL_CLASS'))
    {
      define('OTRA_DUMP_FINAL_CLASS', 'Dump' . (php_sapi_name() === 'cli' ? 'Cli' : 'Web'));
      define('OTRA_NAMESPACED_FINAL_CLASS', 'otra\\tools\\debug\\' . OTRA_DUMP_FINAL_CLASS);
    }

    require_once CORE_PATH . 'tools/debug/' . OTRA_DUMP_FINAL_CLASS . '.php';
    $oldOtraDebugValues = call_user_func('otra\\tools\\debug\\DumpMaster::setDumpConfig', $options);
    call_user_func(OTRA_NAMESPACED_FINAL_CLASS . '::dump', ...$params);
    AllConfig::$debugConfig = $oldOtraDebugValues;
  }
}
