<?php
declare(strict_types=1);

/* Light templating engine */
use otra\MasterController;

if (function_exists('showBlocksVisually') === false)
{
  /**
   * @param int   $key
   * @param array $block
   */
  function showBlockTags(int $key, array $block): void
  {
    ?>
    <div class="otra--block-tags">
      <span class="otra--block-tags--key" title="Position of the block in the stack"><?= $key ?></span>
      <span class="otra--block-tags--depth"
            title="Depth of the block in the OTRA template engine"><?= $block[OTRA_BLOCKS_KEY_INDEX] ?></span>
      <span class="otra--block-tags--markup"
            title="Highest markup of the block or name of the block">&lt;<?= $block[OTRA_BLOCKS_KEY_NAME] ?>&gt;</span>
      <span class="otra--block-tags--ending-block otra--block--ending--<?= $block['endingBlock'] ?? 'false' ?>"
            title="Is this virtual block ending a template block?">Ending block</span>
    </div>
    <?php
  }

  function showBlocksVisually()
  {
    ob_clean();
    ?>
    <link rel="stylesheet" href="<?= CORE_CSS_PATH . 'templateMotor.css' ?>"/> <?php

    foreach (MasterController::$blocksStack as $blockKey => $block)
    {
//      if (isset($block[OTRA_BLOCKS_KEY_PARENT]))
//        var_dump($block);
      ?>
      <div id="block<?= $blockKey ?>" class="otra-block--base">
        <?php showBlockTags($blockKey, $block) ?>
        <pre class="otra--code"><?= htmlentities($block[OTRA_BLOCKS_KEY_CONTENT]) ?></pre>
        <div class="otra--separator"></div>
        <?php
        if (isset($block['replacedBy']))
          echo '<p>Replaced by the <a href="#block' . $block['replacedBy'] . '" title="' .
            htmlentities(MasterController::$blocksStack[$block['replacedBy']][OTRA_BLOCKS_KEY_CONTENT]) . '">block ' .
            $block['replacedBy'] . '</a></p>';

        while ($block[OTRA_BLOCKS_KEY_PARENT] !== null)
        {
          $block = $block[OTRA_BLOCKS_KEY_PARENT];
          $parentBlockKey = array_search($block, MasterController::$blocksStack);
          ?>
          <details class="otra-block--parent--accordion">
            <summary>
              <a href="#block<?= $parentBlockKey ?>">Parent block : <?= $parentBlockKey ?></a>
            </summary>
            <div class="otra-block--parent">
              <?php showBlockTags(0, $block) ?>
              <pre class="otra--code"><?= htmlentities($block[OTRA_BLOCKS_KEY_CONTENT]) ?></pre>
            </div>
          </details><?php
        }
        ?>
      </div><?php
    }
    die;
  }
}
