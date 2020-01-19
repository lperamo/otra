<?php
/**
 * Profiler service
 *
 * @author Lionel Péramo
 */

namespace lib\otra\services;

class ProfilerService
{
  public static function securityCheck()
  {
    if ('dev' !== $_SERVER['APP_ENV'])
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

      require CORE_PATH . 'tools/SqlPrettyPrint.php';

      foreach($requests as &$r)
      {
        ?>
        <div>
          <div class="dbg-left-block dbg-fl">
            <?= t('In file') . ' <span class="dbg-file">', substr($r['file'], strlen(BASE_PATH)), '</span> '
              . t('at line') . '&nbsp;<span class="dbg-line">', $r['line'], '</span>&nbsp;:',
            '<p>', rawSqlPrettyPrint($r['query']), '</p>'?>
          </div>
          <a role="button" class="dbg-fr lb-btn"><?= t('Copy') ?></a>
        </div>
        <?php
      }
    } else
      echo t('No stored queries in '), $file, '.';
  }
}
?>
