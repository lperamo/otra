<?php
declare(strict_types=1);
namespace otra\templating;
/**
 * @author Lionel Péramo
 * @package otra\templating
 */

/* Light templating engine */
use otra\cache\php\BlocksSystem;
use const otra\cache\php\CORE_CSS_PATH;

if (!function_exists(__NAMESPACE__ . '\\showBlocksVisually'))
{
  /**
   * @param array{
   *   content: string,
   *   endingBlock: bool,
   *   index: int,
   *   name: string,
   *   parent?: array,
   *   replacedBy?: int
   * } $block
   */
  function showBlockTags(int|string $key, array $block) : void
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

  function showCode(string $code) : void
  {
    if ($code !== '')
    {?>
    <pre class="otra--code"><!--
    --><strong class="otra--code--container"><mark class="otra--code--container-highlight"><?= htmlentities($code) ?></mark></strong><!--
 --></pre>
    <?php
    } else {
      ?><p>Empty block.</p><?php
    }
  }

  /**
   * Shows the template blocks as seen by the OTRA template engine.
   */
  function showBlocksVisually(bool $page = true) : void
  {
    if ($page)
    {
    ?>
    <link rel="stylesheet" href="<?= CORE_CSS_PATH . 'pages/templateStructure/templateStructure.css' ?>"/>
    <?php
    }
    ?>
    <div<?php if (!$page) { ?> class="template-structure--container"<?php } ?>>
      <h1 class="otra--template-rendering--title">Template rendering</h1>
      <?php
      $replacingBlocks = [];

      /**
       * @var int|string $blockKey
       * @var array{
       *   content: string,
       *   endingBlock: bool,
       *   index: int,
       *   name: string,
       *   parent?: array,
       *   replacedBy?: int
       * } $block
       */
      foreach (BlocksSystem::$blocksStack as $blockKey => $block)
      {
        ?>
        <div id="block<?= $blockKey ?>" class="otra-block--base">
          <?php
          showBlockTags($blockKey, $block);
        showCode($block[BlocksSystem::OTRA_BLOCKS_KEY_CONTENT]);

        if (isset($block[BlocksSystem::OTRA_BLOCKS_KEY_REPLACED_BY]))
        {
          $replacingBlocks[$block[BlocksSystem::OTRA_BLOCKS_KEY_REPLACED_BY]] = $blockKey;
          $templateEngineError = isset(BlocksSystem::$blocksStack[$block[BlocksSystem::OTRA_BLOCKS_KEY_REPLACED_BY]]);
          ?><?php if ($templateEngineError) {?><br><span style="color: #f00 !important">Error - </span><?php }?><p>Replaced by the
          <a href="#block<?= $block[BlocksSystem::OTRA_BLOCKS_KEY_REPLACED_BY] ?>" title="<?=
          $templateEngineError
            ? htmlentities(BlocksSystem::$blocksStack[$block[BlocksSystem::OTRA_BLOCKS_KEY_REPLACED_BY]][BlocksSystem::OTRA_BLOCKS_KEY_CONTENT])
            : 'Error!' ?>
            ">block <?= $block[BlocksSystem::OTRA_BLOCKS_KEY_REPLACED_BY] ?></a></p><?php
        }

        if (isset($replacingBlocks[$blockKey]))
          echo '<p>Replacing the <a href="#block' . $replacingBlocks[$blockKey] . '" title="' .
            htmlentities(BlocksSystem::$blocksStack[$replacingBlocks[$blockKey]][BlocksSystem::OTRA_BLOCKS_KEY_CONTENT]) .
            '">block ' . $replacingBlocks[$blockKey] . '</a></p>';

        $previousParentKeys = '';

          while ($block[BlocksSystem::OTRA_BLOCKS_KEY_PARENT] !== null)
          {
            $block = $block[BlocksSystem::OTRA_BLOCKS_KEY_PARENT];
            $parentBlockKey = array_search($block, BlocksSystem::$blocksStack);
            ?>
            <details class="otra-block--parent--accordion">
              <summary>
                <a href="#block<?= $parentBlockKey ?>">Parent block : <?= $previousParentKeys . $parentBlockKey ?></a>
              </summary>
              <div class="otra-block--parent">
                <?php
                showBlockTags(0, $block);
                showCode($block[BlocksSystem::OTRA_BLOCKS_KEY_CONTENT]);
                ?>
              </div>
            </details><?php
            $previousParentKeys .= $parentBlockKey . ' => ';
          }
          ?>
        </div><?php
      }
    ?></div><?php
  }
}
