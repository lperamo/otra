<?php
declare(strict_types=1);

namespace otra\controllers\profiler;

use otra\{Controller, OtraException, services\ProfilerService};
use const otra\cache\php\{APP_ENV,BASE_PATH,CORE_PATH};
use function otra\tools\t;

/**
 * Class ClearSQLLogsAction
 *
 * @author  Lionel Péramo
 * @package otra\controllers\profiler
 */
class ClearSQLLogsAction extends Controller
{
  /**
   * @param array $otraParams
   * @param array $params
   *
   * @throws OtraException
   */
  public function __construct(array $otraParams = [], array $params = [])
  {
    parent::__construct($otraParams, $params);
    ProfilerService::securityCheck();
    $sqlLogFile = BASE_PATH . 'logs/' . $_SERVER[APP_ENV] . '/sql.txt';
    $handle = fopen($sqlLogFile, 'r+');
    ftruncate($handle, 0);
    fclose($handle);

    require CORE_PATH . 'tools/translate.php';
    echo t('No more stored queries in '), $sqlLogFile, '.';
  }
}

