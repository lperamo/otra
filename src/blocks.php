<?php
declare(strict_types=1);

/* Light templating engine */
use otra\MasterController;

// those functions can be redeclared if we have an exception later, exception that will also use the block system
if (function_exists('block') === false)
{
  define('OTRA_BLOCKS_KEY_CONTENT', 'content');
  define('OTRA_BLOCKS_KEY_INDEX', 'index');
  define('OTRA_BLOCKS_KEY_PARENT', 'parent');
  define('OTRA_BLOCKS_KEY_REPLACED_BY', 'replacedBy');

  /**
   * Begins a template block.
   *
   * @param string      $name
   * @param string|null $inline If we just want an inline block then we echo this string and close the block directly.
   */
  function block(string $name, ?string $inline = null)
  {
    // Storing previous content before doing anything else
    $currentBlock = &MasterController::$currentBlock;
    $currentBlock[OTRA_BLOCKS_KEY_CONTENT] .= ob_get_clean();

    array_push(MasterController::$blocksStack, $currentBlock);

    // Preparing the new block
    $currentBlock = [
      OTRA_BLOCKS_KEY_CONTENT => '',
      OTRA_BLOCKS_KEY_INDEX => ++MasterController::$currentBlocksStackIndex,
      'name' => $name,
      OTRA_BLOCKS_KEY_PARENT => &MasterController::$blocksStack[array_key_last(MasterController::$blocksStack)]
    ];

    // Key of the next $currentBlock in the stack
    $actualKey = count(MasterController::$blocksStack);

    // Is there another block of the same type
    if (array_key_exists($name, MasterController::$blockNames) === true)
    {
      // We retrieve it
      $firstPreviousSameKindBlockKey = MasterController::$blockNames[$name];

      for ($stackKey = MasterController::$blockNames[$name]; $stackKey < $actualKey; ++$stackKey)
      {
        $previousSameKindBlock = &MasterController::$blocksStack[$stackKey];

        // Handles the block replacement system (parent replaced by child)
        if ($currentBlock[OTRA_BLOCKS_KEY_INDEX] > $previousSameKindBlock[OTRA_BLOCKS_KEY_INDEX]
          && $stackKey === $firstPreviousSameKindBlockKey)
          MasterController::$blocksStack[$firstPreviousSameKindBlockKey][OTRA_BLOCKS_KEY_REPLACED_BY] = $actualKey;
      }
    }

    // Updates the list of block types with their position in the stack
    MasterController::$blockNames[$currentBlock['name']] = $actualKey;

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
    $currentBlock = &MasterController::$currentBlock;
    $currentBlock['endingBlock'] = true; // needed for processing parent function
    $currentBlock[OTRA_BLOCKS_KEY_CONTENT] .= ob_get_clean();

    // Updates the list of block types with their position in the stack
    if ($currentBlock['name'] !== 'root'
      && (array_key_exists($currentBlock['name'], MasterController::$blockNames) === true
        && $currentBlock[OTRA_BLOCKS_KEY_INDEX] > MasterController::$blockNames[$currentBlock['name']])
    )
      MasterController::$blockNames[$currentBlock['name']] = count(MasterController::$blocksStack);

    array_push(MasterController::$blocksStack, $currentBlock);

    // Preparing the next block
    MasterController::$currentBlock = [
      OTRA_BLOCKS_KEY_CONTENT => '',
      OTRA_BLOCKS_KEY_INDEX => $currentBlock[OTRA_BLOCKS_KEY_PARENT][OTRA_BLOCKS_KEY_INDEX],
      OTRA_BLOCKS_KEY_PARENT => $currentBlock[OTRA_BLOCKS_KEY_PARENT][OTRA_BLOCKS_KEY_PARENT],
      'name' => $currentBlock[OTRA_BLOCKS_KEY_PARENT]['name']
    ];

    // Start listening the remaining content
    ob_start();
  }

  /**
   * Shows the content of the parent block of the same type.
   */
  function parent()
  {
    // $key is the key of the first block of the same type
    $key = array_search(
      MasterController::$currentBlock['name'],
      array_column(MasterController::$blocksStack, 'name')
    );

    // We put the first block of the same type
    $parentBlock = MasterController::$blocksStack[$key];

    // If this block has been replaced, we jump to replacing block if it is not our current block
    while (array_key_exists(OTRA_BLOCKS_KEY_REPLACED_BY, $parentBlock) === true
      && array_key_exists($parentBlock[OTRA_BLOCKS_KEY_REPLACED_BY], MasterController::$blocksStack) === true)
    {
      $key = $parentBlock[OTRA_BLOCKS_KEY_REPLACED_BY];
      $parentBlock = MasterController::$blocksStack[$key];
    }

    $content = $parentBlock[OTRA_BLOCKS_KEY_CONTENT];

    if (array_key_exists('endingBlock', $parentBlock) === false)
    {
      // We gather all the content of the last same type block before this one
      do
      {
        if (isset(MasterController::$blocksStack[$key + 1]) === false)
          break;

        $block = MasterController::$blocksStack[++$key];
        $content .= $block[OTRA_BLOCKS_KEY_CONTENT];
      } while ($block[OTRA_BLOCKS_KEY_INDEX] !== $parentBlock[OTRA_BLOCKS_KEY_INDEX]);
    }

    echo $content;
  }
}

