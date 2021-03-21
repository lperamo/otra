<?php
declare(strict_types=1);

namespace cache\php
{
  /**
   * Light templating engine "interface".
   *
   * @author Lionel Péramo
   * @package otra\templating
   */
  abstract class BlocksSystem
  {
    public const
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
     * Assembles all the template blocks to returns the final string.
     *
     * @return string
     */
    public static function getTemplate() : string
    {
      $content = '';
      self::$currentBlock[self::OTRA_BLOCKS_KEY_CONTENT] .= ob_get_clean();
      array_push(self::$blocksStack, self::$currentBlock);
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
      foreach(self::$blocksStack as $blockKey => $block)
      {
        // If this block do not have to be replaced and which has not already been shown
        if ((!isset($block[self::OTRA_BLOCKS_KEY_REPLACED_BY])
          || $block[self::OTRA_BLOCKS_KEY_NAME] === 'root'
          || $block[self::OTRA_BLOCKS_KEY_NAME] === '')
          && !in_array($blockKey, $indexesToUnset))
        {
          $content .= $block[self::OTRA_BLOCKS_KEY_CONTENT];
          continue;
        }

        $prevKey = $tmpKey = $blockKey;

        do
        {
          // We add the key to an array of indexes that will not be showed again
          /** @var int $tmpKey */
          if (!in_array($tmpKey, $indexesToUnset))
            $indexesToUnset[]= $tmpKey;

          self::$blocksStack[$prevKey][self::OTRA_BLOCKS_KEY_CONTENT] = '';
          $prevKey = $tmpKey;
          $replaced = isset(self::$blocksStack[$tmpKey][self::OTRA_BLOCKS_KEY_REPLACED_BY]);

          // The block will be replaced, we retrieve the replacing block
          if ($replaced)
          {
            $childrenKey = $tmpKey + 1;
            $startIndex = self::$blocksStack[$tmpKey][self::OTRA_BLOCKS_KEY_INDEX];
            $tmpKey = self::$blocksStack[$tmpKey][self::OTRA_BLOCKS_KEY_REPLACED_BY];

            // the children blocks will be unset/removed
            while (self::$blocksStack[$childrenKey][self::OTRA_BLOCKS_KEY_INDEX] > $startIndex)
            {
              if (!in_array($childrenKey, $indexesToUnset))
                $indexesToUnset[]= $childrenKey++;
            }

            // If there is content after the children blocks but before the ending block
            if (self::$blocksStack[$childrenKey][self::OTRA_BLOCKS_KEY_NAME] === $block[self::OTRA_BLOCKS_KEY_NAME]
              && !in_array($childrenKey, $indexesToUnset))
              $indexesToUnset[]= $childrenKey;
          } else
          {
            // The block will not be replaced, we can add its content
            $content .= self::$blocksStack[$tmpKey][self::OTRA_BLOCKS_KEY_CONTENT];
            self::$blocksStack[$tmpKey][self::OTRA_BLOCKS_KEY_CONTENT] = '';

            // We add the key to an array of indexes that will not be showed again
            if (!in_array($tmpKey, $indexesToUnset))
              $indexesToUnset[]= $tmpKey;
          }
        }
        // The previous block must be replaced, we process the replacing block...
        while($replaced);
      }

      return $content;
    }
  }
}

namespace {
  use cache\php\BlocksSystem;

  /* Light templating engine */
  // those functions can be redeclared if we have an exception later, exception that will also use the block system
  if (!function_exists('block'))
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
    function block(string $name, ?string $inline = null) : void
    {
      // Storing previous content before doing anything else
      /** @var array{content:string, index:int, name:string, parent?:array} $currentBlock */
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
    function endblock() : void
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
      $currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_CONTENT] .=  ob_get_clean();

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
    function parent() : void
    {
      $parentKey = array_search(
        BlocksSystem::$currentBlock[BlocksSystem::OTRA_BLOCKS_KEY_NAME],
        array_column(BlocksSystem::$blocksStack, BlocksSystem::OTRA_BLOCKS_KEY_NAME)
      );

      // We put the first block of the same type
      /** @var array{
       *   content:string,
       *   endingBlock?:bool,
       *   index:int,
       *   name:string,
       *   parent?:array,
       *   replacedBy?:int
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
