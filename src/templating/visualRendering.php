<?php
declare(strict_types=1);

/* Light templating engine */
use cache\php\BlocksSystem;

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
            title="Depth of the block in the OTRA template engine"><?= $block[BlocksSystem::OTRA_BLOCKS_KEY_INDEX] ?></span>
      <span class="otra--block-tags--markup"
            title="Highest markup of the block or name of the block"><?=
        $block[BlocksSystem::OTRA_BLOCKS_KEY_NAME] !== '' ? '&lt;' . $block[BlocksSystem::OTRA_BLOCKS_KEY_NAME] .'&gt;' : 'No name'
      ?></span>
      <span class="otra--block-tags--ending-block otra--block--ending--<?= $block['endingBlock'] ?? 'false' ?>"
            title="Is this virtual block ending a template block?">Ending block</span>
    </div>
    <?php
  }

  function showCode(string $code): void
  {
    if ($code !== '') {
    ?>
    <pre class="otra--code"><!--
    --><b class="otra--code--container"><mark class="otra--code--container-highlight"><?= htmlentities($code) ?></mark></b><!--
 --></pre>
    <?php
    } else {
      ?><p>Empty block.</p><?php
    }
  }

  function showBlocksVisually()
  {
    ob_clean();
    ?>
    <link rel="stylesheet" href="<?= CORE_CSS_PATH . 'templateMotor.css' ?>"/>
    <h1 class="otra--template-rendering--title">Template rendering</h1>
    <?php
    $replacingBlocks = [];

    foreach (BlocksSystem::$blocksStack as $blockKey => $block)
    {
      ?>
      <div id="block<?= $blockKey ?>" class="otra-block--base">
        <?php
        showBlockTags($blockKey, $block);
        showCode($block[BlocksSystem::OTRA_BLOCKS_KEY_CONTENT]);
        ?>
        <?php
        if (isset($block['replacedBy']))
        {
          $replacingBlocks[$block['replacedBy']] = $blockKey;
          echo '<p>Replaced by the <a href="#block' . $block['replacedBy'] . '" title="' .
            htmlentities(BlocksSystem::$blocksStack[$block['replacedBy']][BlocksSystem::OTRA_BLOCKS_KEY_CONTENT]) . '">block ' .
            $block['replacedBy'] . '</a></p>';
        }

        if (isset($replacingBlocks[$blockKey]))
          echo '<p>Replacing the <a href="#block' . $replacingBlocks[$blockKey] . '" title="' .
            htmlentities(BlocksSystem::$blocksStack[$replacingBlocks[$blockKey]][BlocksSystem::OTRA_BLOCKS_KEY_CONTENT]) . '">block ' .
            $replacingBlocks[$blockKey] . '</a></p>';

        while ($block[BlocksSystem::OTRA_BLOCKS_KEY_PARENT] !== null)
        {
          $block = $block[BlocksSystem::OTRA_BLOCKS_KEY_PARENT];
          $parentBlockKey = array_search($block, BlocksSystem::$blocksStack);
          ?>
          <details class="otra-block--parent--accordion">
            <summary>
              <a href="#block<?= $parentBlockKey ?>">Parent block : <?= $parentBlockKey ?></a>
            </summary>
            <div class="otra-block--parent">
              <?php
              showBlockTags(0, $block);
              showCode($block[BlocksSystem::OTRA_BLOCKS_KEY_CONTENT]);
              ?>
            </div>
          </details><?php
        }
        ?>
      </div><?php
    }
    die;
  }
}
