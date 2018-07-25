<?
/**
 * Profiler service
 *
 * @author Lionel Péramo
 */

namespace lib\myLibs\services;

class profilerService
{
  public static function securityCheck()
  {
    if ('Dev' !== $_SESSION['debuglp_'])
    {
      echo 'No hacks.';
      exit (1);
    }
  }

  /**
   * @param string $file
   */
  public static function writeLogs(string $file)
  {
    if ( true === file_exists($file) && '' !== ($contents = file_get_contents($file)))
    {
      $requests = json_decode(str_replace('\\', '\\\\', substr($contents, 0, -1) . ']'), true);

      foreach($requests as &$r)
      {
        echo '<div>',
        '<div class="dbg-left-block dbg-fl">',
        t('In file') . ' <span class="dbg-file">', substr($r['file'], strlen(BASE_PATH)), '</span> ' . t('at line') . '&nbsp;<span class="dbg-line">', $r['line'], '</span> :',
        '<p>', $r['query'], '</p>',
        '</div>',
        '<a role="button" class="dbg-fr lb-btn">' . t('Copy') .'</a>',
        '</div>';
      }
    } else
      echo t('No stored queries in '), $file, '.';
  }
}
?>
