<?php
declare(strict_types=1);
/**
 * Core - Profiler - ClearSQLLogs
 *
 * @author Lionel Péramo */

namespace otra\controllers\profiler;

use otra\{Controller, services\ProfilerService};

/**
 * @package otra\controllers\profiler
 */
class ClearSQLLogsAction extends Controller
{
  /**
   * @param array $baseParams
   * @param array $getParams
   *
   * @throws \otra\OtraException
   */
  public function __construct(array $baseParams = [], array $getParams = [])
  {
    parent::__construct($baseParams, $getParams);
    ProfilerService::securityCheck();
    $file = BASE_PATH . 'logs/' . $_SERVER[APP_ENV] . '/sql.txt';
    $handle = fopen($file, 'r+');
    ftruncate($handle, 0);
    fclose($handle);

    require CORE_PATH . 'tools/translate.php';
    echo t('No more stored queries in '), $file, '.';
  }
}

