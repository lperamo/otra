<?php
/* Light templating engine */

use lib\myLibs\MasterController;

/**
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
    $previousSameKindBlock = &MasterController::$blocksStack[MasterController::$blockNames[$name]];

    if (array_key_exists($name, MasterController::$blockNames) === true
      && $currentBlock['index'] > $previousSameKindBlock['index'])
    {
      // Handles the block replacement system (parent replaced by child)
      $previousSameKindBlock['replacedBy'] = $actualKey;

      // Handles the parent content for parent() function
      $currentBlock['parentContent'] = $previousSameKindBlock['content'];
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

function endblock()
{
  // Storing previous content before doing anything else
  $currentBlock = &MasterController::$currentBlock;
  $currentBlock['content'] .= ob_get_clean();

  // Updates the list of block types with their position in the stack
  if ($currentBlock['name'] !== 'root'
    && (array_key_exists($currentBlock['name'], MasterController::$blockNames) === true
      && $currentBlock['index'] > MasterController::$blockNames[$currentBlock['name']])
  )
  {
    MasterController::$blockNames[$currentBlock['name']] = count(MasterController::$blocksStack);
  }

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
 *
 * @throws \lib\myLibs\LionelException
 */
function parent()
{
  if (array_key_exists('parentContent', MasterController::$currentBlock) === false)
    throw new \lib\myLibs\LionelException('There is no parent for this block ' .
      MasterController::$currentBlock['name']);

  echo MasterController::$currentBlock['parentContent'];
}
?>
