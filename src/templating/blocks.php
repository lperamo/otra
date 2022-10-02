<?php
declare(strict_types=1);

namespace otra\cache\php
{
  /**
   * Light templating engine "interface".
   *
   * @author Lionel Péramo
   * @package otra\templating
   */
  abstract class BlocksSystem
  {
    final public const
      OTRA_BLOCKS_KEY_CONTENT = 'content',
      OTRA_BLOCKS_KEY_ENDING_BLOCK = 'endingBlock',
      OTRA_BLOCKS_KEY_INDEX = 'index',
      OTRA_BLOCKS_KEY_NAME = 'name',
      OTRA_BLOCKS_KEY_PARENT = 'parent',
      OTRA_BLOCKS_KEY_REPLACED_BY = 'replacedBy',
      OTRA_BLOCK_NAME_ROOT = 'root';

    public static array
      /**
       * @var array{
       *   content:string,
       *   endingBlock?:bool,
       *   index:int,
       *   name:string,
       *   parent?:array,
       *   replacedBy?:int
       * }[] $blocksStack
       */
      $blocksStack = [],
      /** @var array<string, int> $blockNames */
      $blockNames = [],
      /** @var array{
       *   content:string,
       *   endingBlock?:bool,
       *   index:int,
       *   name:string,
       *   parent?:array,
       *   replacedBy?:int
       * } $currentBlock
       */
      $currentBlock = [
      'content' => '',
      'index' => 0,
      'name' => 'root',
      'parent' => null
    ];

    public static int
      $currentBlocksStackIndex = 0;

    /**
     * @param int    $maxIndex             Maximum reachable index in the stack
     * @param int    $maxKey               Maximum key of the stack
     * @param int    $blockKey             Position of the block in the stack
     * @param array  $indexesToUnset       Index of blocks to unset (index is the depth of the blocks in the template engine)
     */
    public static function replaceParentBlocks(int $maxIndex, int $maxKey, int $blockKey, array &$indexesToUnset): string
    {
      // should not be shown because it is replaced by another block
      if (in_array($blockKey, $indexesToUnset))
        return '';

      // If this block does not have to be replaced and has not already been shown
      if (!isset(self::$blocksStack[$blockKey][self::OTRA_BLOCKS_KEY_REPLACED_BY])
        || self::$blocksStack[$blockKey][self::OTRA_BLOCKS_KEY_NAME] === 'root'
        || self::$blocksStack[$blockKey][self::OTRA_BLOCKS_KEY_NAME] === '')
      {
        return self::$blocksStack[$blockKey][self::OTRA_BLOCKS_KEY_CONTENT];
      }

      $prevKey = $lastKey = $blockKey;

      // Gets the last block of the same type
      do
      {
        $lastKey = self::$blocksStack[$lastKey][self::OTRA_BLOCKS_KEY_REPLACED_BY];

        if (!in_array($prevKey, $indexesToUnset))
          $indexesToUnset[]= $prevKey;

        $prevKey = $lastKey;
      } while(isset(self::$blocksStack[$lastKey][self::OTRA_BLOCKS_KEY_REPLACED_BY]));

      self::$blocksStack[$blockKey][self::OTRA_BLOCKS_KEY_CONTENT] = self::$blocksStack[$lastKey][self::OTRA_BLOCKS_KEY_CONTENT];

      // We are looking for the real first content of {the named block/this index}
      $initKey = $lastKey - 1;

      while(self::$blocksStack[$initKey][self::OTRA_BLOCKS_KEY_INDEX]
        >= self::$blocksStack[$lastKey][self::OTRA_BLOCKS_KEY_INDEX])
      {
        --$initKey;
      }

      $tmpContent = '';
      $lastChildrenKey = $initKey + 1;

      // iterates on children of the last replacing block of the same type
      while ($lastChildrenKey < $maxKey
        && self::$blocksStack[$lastChildrenKey][self::OTRA_BLOCKS_KEY_INDEX] >=
        self::$blocksStack[$lastKey][self::OTRA_BLOCKS_KEY_INDEX])
      {
        $indexesToUnset[] = $lastChildrenKey;

        if (!isset(self::$blocksStack[$lastChildrenKey][self::OTRA_BLOCKS_KEY_REPLACED_BY]))
          $tmpContent .= self::$blocksStack[$lastChildrenKey][self::OTRA_BLOCKS_KEY_CONTENT];
        else
        {
          // Do the same things in recursive loop if there are replaced blocks in the replacing block
          $replacedBlockKeyToProcess = self::$blocksStack[$lastChildrenKey][self::OTRA_BLOCKS_KEY_REPLACED_BY];
          $tmpContent .= self::replaceParentBlocks(
            $maxIndex,
            $maxKey,
            $replacedBlockKeyToProcess,
            $indexesToUnset
          );

          if (!in_array($replacedBlockKeyToProcess, $indexesToUnset))
            $indexesToUnset[] = $replacedBlockKeyToProcess;
        }

        self::$blocksStack[$lastChildrenKey][self::OTRA_BLOCKS_KEY_CONTENT] = '';
        $lastChildrenKey++;
      }

      // prevents the remaining content of the replaced block to be included
      $nextKey = $blockKey + 1;

      if (self::$blocksStack[$nextKey][self::OTRA_BLOCKS_KEY_INDEX]
        === self::$blocksStack[$blockKey][self::OTRA_BLOCKS_KEY_INDEX]
        && isset(self::$blocksStack[$blockKey][self::OTRA_BLOCKS_KEY_REPLACED_BY]))
      {
        self::$blocksStack[$nextKey][self::OTRA_BLOCKS_KEY_CONTENT] = '';
        $indexesToUnset[] = $nextKey;
      }

      // Iterates on all the blocks of the same index...
      while ($nextKey < $maxKey
        && self::$blocksStack[$nextKey][self::OTRA_BLOCKS_KEY_INDEX]
        >= self::$blocksStack[$blockKey][self::OTRA_BLOCKS_KEY_INDEX])
      {
        $indexesToUnset[] = $nextKey;
        self::$blocksStack[$nextKey][self::OTRA_BLOCKS_KEY_CONTENT] = '';
        ++$nextKey;
      }

      return $tmpContent;
    }

    /**
     * Assembles all the template blocks to return the final string.
     *
     * @return string
     */
    public static function getTemplate() : string
    {
      $content = '';
      self::$currentBlock[self::OTRA_BLOCKS_KEY_CONTENT] .= ob_get_clean();
      self::$blocksStack[] = self::$currentBlock;
      $maxIndex = max(array_column(self::$blocksStack, self::OTRA_BLOCKS_KEY_INDEX));
      $maxKey = count(self::$blocksStack);

      /** @var int[] $indexesToUnset */
      $indexesToUnset = [];

      // Loops through the block stack to compile the final content that have to be shown
      /**
       * @var int $blockKey
       * @var array{
       *   content:string,
       *   endingBlock?:bool,
       *   index:int,
       *   name:string,
       *   parent?:array,
       *   replacedBy?:int
       * } $block
       */
      foreach(array_keys(self::$blocksStack) as $blockKey)
      {
        $content .= self::replaceParentBlocks($maxIndex, $maxKey, $blockKey, $indexesToUnset);
      }

      return $content;
    }
  }

  /* Light templating engine */
  // those functions can be redeclared if we have an exception later, exception that will also use the block system
  if (!function_exists(__NAMESPACE__ . '\\block'))
  {
    /* Little remainder
     * Key is the key of the block stacks array. It is the position of the block in the stack
     * Index is a number that represents the depth of the block in the OTRA template engine
     */
    /**
     * Begins a template block.
     *
     * @param ?string $inline If we just want an inline block then we echo this string and close the block directly.
     */
    function block(string $name, ?string $inline = null): void
    {
      // Storing previous content before doing anything else
      /** @var array{content:string, index:int, name:string, parent?:array} $currentBlock */
      $currentBlock = &BlocksSystem::$currentBlock;
      $currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_CONTENT] .= ob_get_clean();
      BlocksSystem::$blocksStack[] = $currentBlock;

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
        /** @var int $firstPreviousSameKindBlockKey Only put here for Psalm */
        $firstPreviousSameKindBlockKey = BlocksSystem::$blockNames[$name];

        for ($stackKey = $firstPreviousSameKindBlockKey; $stackKey < $actualKey; ++$stackKey)
        {
          /** @var array{
           *   content:string,
           *   endingBlock?:bool,
           *   index:int,
           *   name:string,
           *   parent?:array,
           *   replacedBy?:int
           * } $previousSameKindBlock For Psalm*/
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

            BlocksSystem::$blocksStack[$firstPreviousSameKindBlockKey][BlocksSystem::OTRA_BLOCKS_KEY_REPLACED_BY] =
              $actualKey;
          }
        }
      }

      // Updates the list of block types with their position in the stack if it is not a root or empty block
      if ($currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_NAME] !== BlocksSystem::OTRA_BLOCK_NAME_ROOT
        && $currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_NAME] !== '')
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
    function endblock(): void
    {
      // Storing previous content before doing anything else
      /** @var array{
       *   content:string,
       *   endingBlock?:bool,
       *   index:int,
       *   name:string,
       *   parent?:array,
       *   replacedBy?:int
       * } $currentBlock For Psalm
       */
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
          && $currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_INDEX] >
          BlocksSystem::$blockNames[$currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_NAME]])
      )
        BlocksSystem::$blockNames[$currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_NAME]] = count(BlocksSystem::$blocksStack);

      BlocksSystem::$blocksStack[] = $currentBlock;

      // If the next content is not at the root level maybe it is a block content not explicitly named...
      // so we add 1 to the index
      if (isset($currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_PARENT][BlocksSystem::OTRA_BLOCKS_KEY_INDEX]))
      {
        $nextIndex = ($currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_PARENT][BlocksSystem::OTRA_BLOCKS_KEY_NAME] !== 'root')
          ? $currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_PARENT][BlocksSystem::OTRA_BLOCKS_KEY_INDEX] + 1
          : $currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_PARENT][BlocksSystem::OTRA_BLOCKS_KEY_INDEX];
      } else
        $nextIndex = 0;

      // Preparing the next block
      BlocksSystem::$currentBlock = [
        BlocksSystem::OTRA_BLOCKS_KEY_CONTENT => '',
        BlocksSystem::OTRA_BLOCKS_KEY_INDEX => $nextIndex,
        BlocksSystem::OTRA_BLOCKS_KEY_PARENT =>
          $currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_PARENT][BlocksSystem::OTRA_BLOCKS_KEY_PARENT] ?? null,
        BlocksSystem::OTRA_BLOCKS_KEY_NAME =>
          $currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_PARENT][BlocksSystem::OTRA_BLOCKS_KEY_NAME] ?? ''
      ];

      // Start listening the remaining content
      ob_start();
    }

    /**
     * Shows the content of the parent block of the same type.
     */
    function parent(): void
    {
      $parentKey = array_search(
        BlocksSystem::$currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_NAME],
        array_column(BlocksSystem::$blocksStack, BlocksSystem::OTRA_BLOCKS_KEY_NAME)
      );

      // We put the first block of the same type
      /** @var array{
       *   content: string,
       *   endingBlock?: bool,
       *   index: int,
       *   name: string,
       *   parent?: array,
       *   replacedBy?: int
       * } $parentBlock For Psalm */
      $parentBlock = BlocksSystem::$blocksStack[$parentKey];

      // If this block has been replaced, we jump to replacing block if it is not our current block
      while (isset($parentBlock[BlocksSystem::OTRA_BLOCKS_KEY_REPLACED_BY])
        && isset(BlocksSystem::$blocksStack[$parentBlock[BlocksSystem::OTRA_BLOCKS_KEY_REPLACED_BY]]))
      {
        $parentKey = $parentBlock[BlocksSystem::OTRA_BLOCKS_KEY_REPLACED_BY];
        $parentBlock = BlocksSystem::$blocksStack[$parentKey];
      }

      $content = $parentBlock[BlocksSystem::OTRA_BLOCKS_KEY_CONTENT];

      // Gets the content from all the blocks contained by the parent block
      if (!isset($parentBlock[BlocksSystem::OTRA_BLOCKS_KEY_ENDING_BLOCK]))
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
