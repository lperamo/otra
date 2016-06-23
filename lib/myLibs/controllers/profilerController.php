<?
/**
 * Backend of the LPCMS
 *
 * @author Lionel Péramo */

namespace lib\myLibs\controllers;

use lib\myLibs\Controller,
    config\Router;

class profilerController extends Controller
{
  public function preExecute()
  {
    if ('Dev' !== $_SESSION['debuglp_'])
    {
      echo 'No hacks.';
      exit (1);
    }
  }

  public function indexAction()
  {
    echo '<div id="profiler" class="profiler">
      <div>
        <a id="dbg-hide-profiler" role="button" class="lb-btn dbg-marginR5">Hide the profiler</a>
        <a id="dbg-clear-sql-logs" role="button" class="lb-btn dbg-marginR5">Clear SQL logs</a>
        <a id="dbg-refresh-sql-logs" role="button" class="lb-btn">Refresh SQL logs</a><br><br>
      </div>
      <div id="dbg-sql-logs" class="dbg-sql-logs">';

    self::writeLogs(BASE_PATH . 'logs/sql.txt');

    echo '</div></div>';
  }

  /**
   * @param string $file
   */
  private static function writeLogs(string $file)
  {
    if ( true === file_exists($file) && '' !== ($contents = file_get_contents($file)))
    {
      $requests = json_decode(str_replace('\\', '\\\\', substr($contents, 0, -1) . ']'), true);

      foreach($requests as $r)
      {
        echo '<div>',
            '<div class="dbg-left-block dbg-fl">',
              'In file <span class="dbg-file">', substr($r['file'], strlen(BASE_PATH)), '</span> at line&nbsp;<span class="dbg-line">', $r['line'], '</span> :',
              '<p>', $r['query'], '</p>',
            '</div>',
            '<a role="button" class="dbg-fr lb-btn">Copy</a>',
          '</div>';
      }
    } else
      echo 'No stored queries in ', $file, '.';
  }

  public function clearSQLLogsAction()
  {
    $file = BASE_PATH . 'logs/sql.txt';
    $handle = fopen($file, 'r+');
    ftruncate($handle, 0);
    fclose($handle);

    echo 'No more stored queries in ', $file, '.';
  }

  public function refreshSQLLogsAction() { self::writeLogs(BASE_PATH . 'logs/sql.txt'); }
}
?>
