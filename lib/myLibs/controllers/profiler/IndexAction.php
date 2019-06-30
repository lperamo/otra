<?
/**
 * LPFramework - Core - Profiler - Index
 *
 * @author Lionel Péramo */

namespace lib\myLibs\controllers\profiler;

use lib\myLibs\{Controller, services\ProfilerService};

class IndexAction extends Controller
{
  public function indexAction()
  {
    ProfilerService::securityCheck();
    echo '<div id="profiler" class="profiler">
      <div>
        <a id="dbg-hide-profiler" role="button" class="lb-btn dbg-marginR5">' . t('Hide the profiler') . '</a>
        <a id="dbg-clear-sql-logs" role="button" class="lb-btn dbg-marginR5">' . t('Clear SQL logs'). '</a>
        <a id="dbg-refresh-sql-logs" role="button" class="lb-btn">' . t('Refresh SQL logs') . '</a><br><br>
      </div>
      <div id="dbg-sql-logs" class="dbg-sql-logs">';

    ProfilerService::writeLogs(BASE_PATH . 'logs/' . $_SERVER['APP_ENV'] . '/sql.txt');

    echo '</div></div>';
  }
}
?>
