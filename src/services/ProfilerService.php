<?php
/**
 * Profiler service
 *
 * @author  Lionel Péramo
 */
declare(strict_types=1);

namespace otra\services;

use otra\OtraException;
use const otra\cache\php\{APP_ENV,BASE_PATH,CORE_PATH,DEV};
use function otra\tools\{rawSqlPrettyPrint, trans};

/**
 * @package otra\services
 */
class ProfilerService
{
  /**
   * @throws OtraException
   */
  public static function securityCheck() : void
  {
    if (DEV !== $_SERVER[APP_ENV])
    {
      echo 'No hacks.';
      throw new OtraException(code: 1, exit: true);
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
      return trans('No stored queries in ') . $file . '.';

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
          <?= trans('In file') . ' <span class="profiler--sql-logs--element--file" title="Click to select">', substr($request['file'],
            $basePathLength), '</span>:<span class="profiler--sql-logs--element--line" title="Click to select">', $request['line'],
          '</span>&nbsp;:',
          rawSqlPrettyPrint($request['query']) ?>
        </div>
        <button type="button" class="profiler--sql-logs--element--ripple ripple"><?= trans('Copy') ?></button>
      </div>
      <?php
    }
    return ob_get_clean();
  }
}
