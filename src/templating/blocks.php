<?php
declare(strict_types=1);

namespace cache\php {
  /**
   * Class BlocksSystem
   */
  abstract class BlocksSystem {
    public const
      OTRA_BLOCKS_KEY_CONTENT = 'content',
      OTRA_BLOCKS_KEY_ENDING_BLOCK = 'endingBlock',
      OTRA_BLOCKS_KEY_INDEX = 'index',
      OTRA_BLOCKS_KEY_NAME = 'name',
      OTRA_BLOCKS_KEY_PARENT = 'parent',
      OTRA_BLOCKS_KEY_REPLACED_BY = 'replacedBy',
      OTRA_BLOCK_NAME_ROOT = 'root';

    public static array
      $blocksStack = [],
      $blockNames = [],
      $currentBlock = [
        'content' => '',
        'index' => 0,
        'name' => 'root',
        'parent' => null
      ];

    public static int
      $currentBlocksStackIndex = 0;

    /**
     * Assembles all the template blocks to returns the final string.
     *
     * @return string
     */
    public static function getTemplate() : string
    {
      $content = '';
      BlocksSystem::$currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_CONTENT] .= ob_get_clean();
      array_push(BlocksSystem::$blocksStack, BlocksSystem::$currentBlock);
      $indexesToUnset = [];

      // Loops through the block stack to compile the final content that have to be shown
      foreach(BlocksSystem::$blocksStack as $blockKey => &$block)
      {
        $blockExists = array_key_exists($block[BlocksSystem::OTRA_BLOCKS_KEY_NAME], BlocksSystem::$blockNames);

        // If there are no other blocks with this name...
        if (!$blockExists
          || $block[BlocksSystem::OTRA_BLOCKS_KEY_NAME] === 'root'
          || $block[BlocksSystem::OTRA_BLOCKS_KEY_NAME] === '')
        {
          $content .= $block[BlocksSystem::OTRA_BLOCKS_KEY_CONTENT];
          continue;
        }

        // If there are other blocks with this name...
        $goodBlock = $block;

        // We seeks for the last block with this name and we adds its content
        while(isset($goodBlock[BlocksSystem::OTRA_BLOCKS_KEY_REPLACED_BY]))
        {
          $tmpKey = $blockKey - 1;

          do
          {
            ++$tmpKey;
            $tmpBlock = BlocksSystem::$blocksStack[$tmpKey];

            // Empties the block content, marks it to unset and returns the next block
            BlocksSystem::$blocksStack[$tmpKey][BlocksSystem::OTRA_BLOCKS_KEY_CONTENT] = '';

            if (!in_array($tmpBlock[BlocksSystem::OTRA_BLOCKS_KEY_INDEX], $indexesToUnset))
              $indexesToUnset[] = $tmpBlock[BlocksSystem::OTRA_BLOCKS_KEY_INDEX];

            $nextTmpBlock = BlocksSystem::$blocksStack[$tmpKey + 1];
          } while(
            $nextTmpBlock[BlocksSystem::OTRA_BLOCKS_KEY_PARENT] === $tmpBlock
            && $nextTmpBlock[BlocksSystem::OTRA_BLOCKS_KEY_NAME] !== $block[BlocksSystem::OTRA_BLOCKS_KEY_NAME]
          );

          $goodBlock = BlocksSystem::$blocksStack[$goodBlock[BlocksSystem::OTRA_BLOCKS_KEY_REPLACED_BY]];
        }

        unset($tmpKey, $tmpBlock, $nextTmpBlock);

        // We must also not show the endings blocks that have been replaced
        if (!in_array($goodBlock[BlocksSystem::OTRA_BLOCKS_KEY_INDEX], $indexesToUnset))
          $content .= $goodBlock[BlocksSystem::OTRA_BLOCKS_KEY_CONTENT];

        if (isset($block[BlocksSystem::OTRA_BLOCKS_KEY_REPLACED_BY]))
          BlocksSystem::$blocksStack[$block[BlocksSystem::OTRA_BLOCKS_KEY_REPLACED_BY]][BlocksSystem::OTRA_BLOCKS_KEY_CONTENT] = '';
      }

      return $content;
    }
  }
}

namespace {
  use otra\BlocksSystem;

  /* Light templating engine */
  // those functions can be redeclared if we have an exception later, exception that will also use the block system
  if (function_exists('block') === false)
  {
    /* Little remainder
     * Key is the key of the block stacks array. It is the position of the block in the stack
     * Index is a number that represents the depth of the block in the OTRA template engine
     */
    /**
     * Begins a template block.
     *
     * @param string      $name
     * @param string|null $inline If we just want an inline block then we echo this string and close the block directly.
     */
    function block(string $name, ?string $inline = null)
    {
      // Storing previous content before doing anything else
      $currentBlock = &BlocksSystem::$currentBlock;
      $currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_CONTENT] .= ob_get_clean();
      array_push(BlocksSystem::$blocksStack, $currentBlock);

      // Preparing the new block
      $currentBlock = [
        BlocksSystem::OTRA_BLOCKS_KEY_CONTENT => '',
        BlocksSystem::OTRA_BLOCKS_KEY_INDEX => ++BlocksSystem::$currentBlocksStackIndex,
        BlocksSystem::OTRA_BLOCKS_KEY_NAME => $name,
        BlocksSystem::OTRA_BLOCKS_KEY_PARENT => BlocksSystem::$blocksStack[array_key_last(BlocksSystem::$blocksStack)]
      ];

      // Key of the next $currentBlock (new actual block) in the stack
      $actualKey = count(BlocksSystem::$blocksStack);

      // Is there another block of the same type
      if (isset(BlocksSystem::$blockNames[$name]))
      {
        // We retrieve it
        $firstPreviousSameKindBlockKey = BlocksSystem::$blockNames[$name];

        for ($stackKey = $firstPreviousSameKindBlockKey; $stackKey < $actualKey; ++$stackKey)
        {
          $previousSameKindBlock = &BlocksSystem::$blocksStack[$stackKey];

          if ($currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_NAME] === BlocksSystem::OTRA_BLOCK_NAME_ROOT
            || $currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_NAME] === '')
            continue;

          // Handles the block replacement system (parent replaced by child)
          if (
            $currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_INDEX] > $previousSameKindBlock[BlocksSystem::OTRA_BLOCKS_KEY_INDEX]
            && $stackKey === $firstPreviousSameKindBlockKey)
          {
            // If the block to replace have a parent
            if (BlocksSystem::$blocksStack[$firstPreviousSameKindBlockKey][BlocksSystem::OTRA_BLOCKS_KEY_PARENT] !== null
              && $currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_PARENT][BlocksSystem::OTRA_BLOCKS_KEY_NAME] !== BlocksSystem::OTRA_BLOCK_NAME_ROOT
              && $currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_PARENT][BlocksSystem::OTRA_BLOCKS_KEY_NAME] !== '')
            {
              $currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_PARENT] =
                BlocksSystem::$blocksStack[$firstPreviousSameKindBlockKey][BlocksSystem::OTRA_BLOCKS_KEY_PARENT];
            }

            BlocksSystem::$blocksStack[$firstPreviousSameKindBlockKey][BlocksSystem::OTRA_BLOCKS_KEY_REPLACED_BY] = $actualKey;
          }
        }
      }

      // Updates the list of block types with their position in the stack if it is not a root or empty block
      if ($currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_NAME] !== BlocksSystem::OTRA_BLOCK_NAME_ROOT && $currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_NAME] !== '')
        BlocksSystem::$blockNames[$currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_NAME]] = $actualKey;

      // Start listening for the block we just prepared
      ob_start();

      // If we just want an inline block then we echo the content and close the block directly
      if ($inline !== null)
      {
        echo $inline;
        endblock();
      }
    }

    /**
     * Ends a template block.
     */
    function endblock()
    {
      // Storing previous content before doing anything else
      $currentBlock = &BlocksSystem::$currentBlock;
      $currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_ENDING_BLOCK] = true; // needed for processing parent function
      $currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_CONTENT] .= ob_get_clean();

      // Updates the list of block types with their position in the stack
      // if the block name is different from "root" and "''"
      // if there is already a block registered with that name
      // if the block index is deeper than the index of the registered block
      if ($currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_NAME] !== BlocksSystem::OTRA_BLOCK_NAME_ROOT
        && $currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_NAME] !== ''
        && (isset(BlocksSystem::$blockNames[$currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_NAME]])
          && $currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_INDEX] > BlocksSystem::$blockNames[$currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_NAME]])
      )
        BlocksSystem::$blockNames[$currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_NAME]] = count(BlocksSystem::$blocksStack);

      array_push(BlocksSystem::$blocksStack, $currentBlock);

      // Preparing the next block
      BlocksSystem::$currentBlock = [
        BlocksSystem::OTRA_BLOCKS_KEY_CONTENT => '',
        BlocksSystem::OTRA_BLOCKS_KEY_INDEX => $currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_PARENT][BlocksSystem::OTRA_BLOCKS_KEY_INDEX] ?? 0,
        BlocksSystem::OTRA_BLOCKS_KEY_PARENT => $currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_PARENT][BlocksSystem::OTRA_BLOCKS_KEY_PARENT] ?? null,
        BlocksSystem::OTRA_BLOCKS_KEY_NAME => $currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_PARENT][BlocksSystem::OTRA_BLOCKS_KEY_NAME] ?? ''
      ];

      // Start listening the remaining content
      ob_start();
    }

    /**
     * Shows the content of the parent block of the same type.
     */
    function parent()
    {
      $parentKey = array_search(
        BlocksSystem::$currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_NAME],
        array_column(BlocksSystem::$blocksStack, BlocksSystem::OTRA_BLOCKS_KEY_NAME)
      );

      // We put the first block of the same type
      $parentBlock = BlocksSystem::$blocksStack[$parentKey];

      // If this block has been replaced, we jump to replacing block if it is not our current block
      while (array_key_exists(BlocksSystem::OTRA_BLOCKS_KEY_REPLACED_BY, $parentBlock)
        && array_key_exists($parentBlock[BlocksSystem::OTRA_BLOCKS_KEY_REPLACED_BY], BlocksSystem::$blocksStack))
      {
        $parentKey = $parentBlock[BlocksSystem::OTRA_BLOCKS_KEY_REPLACED_BY];
        $parentBlock = BlocksSystem::$blocksStack[$parentKey];
      }

      $content = $parentBlock[BlocksSystem::OTRA_BLOCKS_KEY_CONTENT];

      // Gets the content from all the blocks contained by the parent block
      if (!array_key_exists(BlocksSystem::OTRA_BLOCKS_KEY_ENDING_BLOCK, $parentBlock))
      {
        // We gather all the content of the last same type block before this one
        do
        {
          if (!isset(BlocksSystem::$blocksStack[$parentKey + 1]))
            break;

          $block = BlocksSystem::$blocksStack[++$parentKey];
          $content .= $block[BlocksSystem::OTRA_BLOCKS_KEY_CONTENT];
        } while ($block[BlocksSystem::OTRA_BLOCKS_KEY_INDEX] !== $parentBlock[BlocksSystem::OTRA_BLOCKS_KEY_INDEX]);
      }

      echo $content;
    }
  }
}
