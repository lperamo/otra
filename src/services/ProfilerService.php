<?php
declare(strict_types=1);
/**
 * Profiler service
 *
 * @author Lionel Péramo
 */

namespace otra\services;

/**
 * @package otra\services
 */
class ProfilerService
{
  public static function securityCheck()
  {
    if ('dev' !== $_SERVER[APP_ENV])
    {
      echo 'No hacks.';
      exit (1);
    }
  }

  /**
   * @param string $file
   *
   * @return false|string
   */
  public static function getLogs(string $file)
  {
    if ( false === file_exists($file) || '' === ($contents = file_get_contents($file)))
      return t('No stored queries in ') . $file . '.';

    $requests = json_decode(
      str_replace(['\\', '},]'], ['\\\\', '}]'], substr($contents, 0, -1) . ']'),
      true
    );
    require CORE_PATH . 'tools/sqlPrettyPrint.php';

    ob_start();
    foreach($requests as $r)
    {
      ?>
      <div>
        <div class="dbg-left-block dbg-fl">
          <?= t('In file') . ' <span class="dbg-file">', substr($r['file'], strlen(BASE_PATH)), '</span> '
            . t('at line') . '&nbsp;<span class="dbg-line">', $r['line'], '</span>&nbsp;:',
          '<p>', rawSqlPrettyPrint($r['query']), '</p>'?>
        </div>
        <button class="dbg-fr lb-btn"><?= t('Copy') ?></button>
      </div>
      <?php
    }
    return ob_get_clean();
  }
}
