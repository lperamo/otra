<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\tools\files
 */

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
        $traceFile = str_replace('\\', '/', $contextItem['file']);

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
