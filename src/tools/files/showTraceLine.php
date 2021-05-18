<?php
/**
 * @author  Lionel Péramo
 * @package otra\tools\files
 */
declare(strict_types=1);

namespace otra\tools\files;
use const otra\cache\php\{BASE_PATH, DIR_SEPARATOR};
use function otra\tools\getSourceFromFile;

/**
 * @param array{class: string, file: string, function:string, type:string, line: int} $contextItem
 */
function showTraceLine(array $contextItem) : void
{
  $hasFile = isset($contextItem['file']);
  ?>
  <details class="accordion">
    <summary class="accordion"><!--
      --><?php
      echo ($contextItem['class'] ?? ''), ($contextItem['type'] ?? ''), ($contextItem['function'] ?? '-'), ' in ';
      $traceLine = ($contextItem['line'] ?? '-');

      if ($hasFile)
      {
        $traceFile = str_replace('\\', DIR_SEPARATOR, $contextItem['file']);

        if (str_contains($traceFile, BASE_PATH))
        {
          ?><span class="exception-main--color--file-and-line" title="' . $traceFile . '">
          <?= substr($traceFile, BASE_PATH_LENGTH) . ':' . $traceLine ?>
          </span>
          <?php
        } else
          echo $traceFile;
      } else
        echo '-' . $traceLine;
      ?>
    </summary>
    <div class="accordion--block">
      <?php
      if ($hasFile && isset($contextItem['line']))
      {
        ?><pre class="exception-main--code-block"><?php
        echo getSourceFromFile($contextItem['file'], $contextItem['line']);
        ?></pre>
        <?php
        if (!empty($contextItem['variables']))
          dump($contextItem['variables']);
      }
      ?>
    </div>
  </details><!--
  --><?php
}
