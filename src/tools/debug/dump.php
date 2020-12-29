<?php
declare(strict_types=1);

use config\AllConfig;

/**
 * A nice dump function that takes as much parameters as we want to put.
 * Somewhat disables XDebug if some parameters are true.
 *
 * @param array $options    [0 => Affects the amount of array children and object's properties shown
 *                           1 => Affects the maximum string length shown
 *                           2 => Affects the array and object's depths shown]
 * @param array ...$params
 */

function dump(array $options = [], ... $params) : void
{
  if (!defined('OTRA_DUMP_VERSION'))
  {
    define('OTRA_DUMP_VERSION', php_sapi_name() === 'cli' ? 'Cli' : 'Web');
    define('OTRA_DUMP_FINAL_CLASS', 'Dump' . OTRA_DUMP_VERSION);
    define('OTRA_NAMESPACED_FINAL_CLASS', 'otra\\' . OTRA_DUMP_FINAL_CLASS);
  }

  require_once CORE_PATH . 'tools/debug/' . OTRA_DUMP_FINAL_CLASS . '.php';
  $oldOtraDebugValues = call_user_func(OTRA_NAMESPACED_FINAL_CLASS . '::setDumpConfig', $options);
  call_user_func(OTRA_NAMESPACED_FINAL_CLASS . '::dump', ...$params);
  AllConfig::$debugConfig = $oldOtraDebugValues;
}
