<?php
declare(strict_types=1);

/**
 * Profiler service
 *
 * @author Lionel Péramo
 * @package otra\services
 */

namespace otra\services;

/**
 * @package otra\services
 */
class ProfilerService
{
  /**
   * @throws \otra\OtraException
   */
  public static function securityCheck() : void
  {
    if ('dev' !== $_SERVER[APP_ENV])
    {
      echo 'No hacks.';
      throw new \otra\OtraException('', 1, '', NULL, [], true);
    }
  }

  /**
   * @param string $file
   *
   * @return false|string
   */
  public static function getLogs(string $file) : false|string
  {
    if (!file_exists($file) || '' === ($contents = file_get_contents($file)))
      return t('No stored queries in ') . $file . '.';

    /** @var array{file:string, line:int, query:string}[] $requests */
    $requests = json_decode(
      str_replace(['\\', '},]'], ['\\\\', '}]'], substr($contents, 0, -1) . ']'),
      true
    );

    require CORE_PATH . 'tools/sqlPrettyPrint.php';

    $basePathLength = strlen(BASE_PATH);
    ob_start();
    foreach($requests as $request)
    {
      ?>
      <div class="profiler--sql-logs--element">
        <div class="profiler--sql-logs--element--left-block">
          <?= t('In file') . ' <span class="profiler--sql-logs--element--file">', substr($request['file'],
            $basePathLength), '</span> '
            . t('at line') . '&nbsp;<span class="profiler--sql-logs--element--line">', $request['line'],
          '</span>&nbsp;:',
          rawSqlPrettyPrint($request['query']) ?>
        </div>
        <button class="profiler--sql-logs--element--ripple ripple"><?= t('Copy') ?></button>
      </div>
      <?php
    }
    return ob_get_clean();
  }
}
