<?
/** Backend of the LPCMS
 *
 * @author Lionel Péramo */

namespace lib\myLibs\core\controllers;

use lib\myLibs\core\Controller,
    lib\myLibs\core\bdd\Sql,
    lib\myLibs\core\Session,
    config\Router;

class profilerController extends Controller
{
  public function preExecute(){
    if('Dev' !== $_SESSION['debuglp_'])
      die('No hacks.');
  }

  public function indexAction(){
    $file = BASE_PATH . '/logs/sql.txt';
    echo '<div id="profiler">
      <a id="dbgHideProfiler" role="button" class="lbBtn dbg_marginR5">Hide the profiler</a>
      <a id="dbgClearSQLLogs" role="button" class="lbBtn dbg_marginR5">Clear SQL logs</a>
      <a id="dbgRefreshSQLLogs" role="button" class="lbBtn">Refresh SQL logs</a><br><br>';

    if(file_exists($file))
    {
      $requests = json_decode(substr(file_get_contents($file), 0, -1) . ']', true);
      foreach($requests as $r)
      {
        echo '<div><div class="dbg_leftBlock dbg_fl">In file <span class="dbg_file">', $r['file'], '</span> at line <span class="dbg_line">', $r['line'], '</span>: <p>', $r['query'], '</p></div><a role="button" class="dbg_fr lbBtn">Copy</a></div>';
      }
    }else
      echo 'There is no stored query.', $file, '.';

    echo '</div>';
  }

  public function clearSQLLogsAction(){
    $handle = fopen(BASE_PATH . '/logs/sql.txt', 'r+');
    ftruncate($handle, rand(1, filesize($filename)));
    fclose($handle);
  }
}
?>
