<?php
declare(strict_types=1);
namespace otra\controllers\profiler;

use otra\{Controller, services\ProfilerService};

/**
 * @author Lionel Péramo
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
    $sqlLogFile = BASE_PATH . 'logs/' . $_SERVER[APP_ENV] . '/sql.txt';
    $handle = fopen($sqlLogFile, 'r+');
    ftruncate($handle, 0);
    fclose($handle);

    require CORE_PATH . 'tools/translate.php';
    echo t('No more stored queries in '), $sqlLogFile, '.';
  }
}

