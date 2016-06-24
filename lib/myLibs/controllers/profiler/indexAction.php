<?
/**
 * LPFramework - Core - Profiler - Index
 *
 * @author Lionel Péramo */

namespace lib\myLibs\controllers;

use lib\myLibs\{Controller, services\profilerService};

class indexAction extends Controller
{
  public function indexAction()
  {
    profilerService::securityCheck();
    echo '<div id="profiler" class="profiler">
      <div>
        <a id="dbg-hide-profiler" role="button" class="lb-btn dbg-marginR5">Hide the profiler</a>
        <a id="dbg-clear-sql-logs" role="button" class="lb-btn dbg-marginR5">Clear SQL logs</a>
        <a id="dbg-refresh-sql-logs" role="button" class="lb-btn">Refresh SQL logs</a><br><br>
      </div>
      <div id="dbg-sql-logs" class="dbg-sql-logs">';

    profilerService::writeLogs(BASE_PATH . 'logs/sql.txt');

    echo '</div></div>';
  }
}
?>
