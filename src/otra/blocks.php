<?php
/* Light templating engine */

use lib\otra\MasterController;

// those functions can be redeclared if we have an exception later, exception that will also use the block system
if (function_exists('block') === false)
{
  /**
   * Begins a template block.
   *
   * @param string $name
   * @param string $inline If we just want an inline block then we echo this string and close the block directly.
   */
  function block(string $name, string $inline = '')
  {
    // Storing previous content before doing anything else
    $currentBlock = &MasterController::$currentBlock;
    $currentBlock['content'] .= ob_get_clean();

    array_push(MasterController::$blocksStack, $currentBlock);

    // Preparing the new block
    $currentBlock = [
      'content' => '',
      'index' => ++MasterController::$currentBlocksStackIndex,
      'name' => $name,
      'parent' => &MasterController::$blocksStack[array_key_last(MasterController::$blocksStack)]
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
        if ($currentBlock['index'] > $previousSameKindBlock['index']
          && $stackKey === $firstPreviousSameKindBlockKey)
          MasterController::$blocksStack[$firstPreviousSameKindBlockKey]['replacedBy'] = $actualKey;
      }
    }

    // Updates the list of block types with their position in the stack
    MasterController::$blockNames[$currentBlock['name']] = $actualKey;

    // Start listening for the block we just prepared
    ob_start();

    // If we just want an inline block then we echo the content and close the block directly
    if ($inline !== '')
    {
      echo $inline;
      endBlock();
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
    $currentBlock['content'] .= ob_get_clean();

    // Updates the list of block types with their position in the stack
    if ($currentBlock['name'] !== 'root'
      && (array_key_exists($currentBlock['name'], MasterController::$blockNames) === true
        && $currentBlock['index'] > MasterController::$blockNames[$currentBlock['name']])
    )
      MasterController::$blockNames[$currentBlock['name']] = count(MasterController::$blocksStack);

    array_push(MasterController::$blocksStack, $currentBlock);

    // Preparing the next block
    MasterController::$currentBlock = [
      'content' => '',
      'index' => $currentBlock['parent']['index'],
      'parent' => $currentBlock['parent']['parent'],
      'name' => $currentBlock['parent']['name']
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
    while (array_key_exists('replacedBy', $parentBlock) === true
      && array_key_exists($parentBlock['replacedBy'], MasterController::$blocksStack) === true)
    {
      $key = $parentBlock['replacedBy'];
      $parentBlock = MasterController::$blocksStack[$key];
    }

    $content = $parentBlock['content'];

    if (array_key_exists('endingBlock', $parentBlock) === false)
    {
      // We gather all the content of the last same type block before this one
      do
      {
        if (isset(MasterController::$blocksStack[$key + 1]) === false)
          break;

        $block = MasterController::$blocksStack[++$key];
        $content .= $block['content'];
      } while ($block['index'] !== $parentBlock['index']);
    }

    echo $content;
  }
}
?>
