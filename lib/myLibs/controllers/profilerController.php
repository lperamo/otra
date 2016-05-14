<?
/**
 * Backend of the LPCMS
 *
 * @author Lionel Péramo */

namespace lib\myLibs\controllers;

use lib\myLibs\Controller,
    lib\myLibs\bdd\Sql,
    lib\myLibs\Session,
    config\Router;

class profilerController extends Controller
{
  public function preExecute(){
    if('Dev' !== $_SESSION['debuglp_'])
      die('No hacks.');
  }

  public function indexAction($refresh = false)
  {
    echo '<div id="profiler">
      <div>
        <a id="dbgHideProfiler" role="button" class="lbBtn dbg_marginR5">Hide the profiler</a>
        <a id="dbgClearSQLLogs" role="button" class="lbBtn dbg_marginR5">Clear SQL logs</a>
        <a id="dbgRefreshSQLLogs" role="button" class="lbBtn">Refresh SQL logs</a><br><br>
      </div>
      <div id="dbgSQLLogs">';

    self::writeLogs(BASE_PATH . 'logs/sql.txt');

    echo '</div></div>';
  }

  private static function writeLogs($file)
  {
    if(file_exists($file) && '' != ($contents = file_get_contents($file)))
    {
      $requests = json_decode(str_replace('\\', '\\\\', substr($contents, 0, -1) . ']'), true);
      // print_r(substr($contents, 0, -1) . ']');die;
      foreach($requests as $r)
      {
        echo '<div><div class="dbg_leftBlock dbg_fl">In file <span class="dbg_file">', $r['file'], '</span> at line <span class="dbg_line">', $r['line'], '</span>: <p>', $r['query'], '</p></div><a role="button" class="dbg_fr lbBtn">Copy</a></div>';
      }
    }else
      echo 'No stored queries in ', $file, '.';
  }

  public function clearSQLLogsAction()
  {
    $file = BASE_PATH . 'logs/sql.txt';
    $handle = fopen($file, 'r+');
    ftruncate($handle, 0);
    fclose($handle);

    echo 'No more stored queries in ' , $file , '.';
  }

  public function refreshSQLLogsAction() { self::writeLogs(BASE_PATH . 'logs/sql.txt'); }
}
?>
