<?php

use lib\myLibs\MasterController;

function block(string $name, string $inline = '')
{
  $currentBlock = &MasterController::$currentBlock;
  $currentBlock['content'] .= ob_get_clean();

  array_push(MasterController::$blocksStack, $currentBlock);

  $currentBlock = [
    'content' => '',
    'index' => ++MasterController::$currentBlocksStackIndex,
    'name' => $name,
    'parent' => &MasterController::$blocksStack[array_key_last(MasterController::$blocksStack)]
  ];

  if ($name !== 'root')
  {
    $actualKey = count(MasterController::$blocksStack);

    if (array_key_exists($name, MasterController::$blockNames) === true)
    {
      $previousSameKindBlock = &MasterController::$blocksStack[MasterController::$blockNames[$name]];

      if (array_key_exists($name, MasterController::$blockNames) === true
        && $currentBlock['index'] > $previousSameKindBlock['index'])
      {
        $previousSameKindBlock['replacedBy'] = $actualKey;
      }
    }

    MasterController::$blockNames[$currentBlock['name']] = $actualKey;
  }

  ob_start();

  if ($inline !== '')
  {
    echo $inline;
    endBlock();
  }
}

function endblock()
{
  $currentBlock = &MasterController::$currentBlock;
  $currentBlock['content'] .= ob_get_clean();

  if ($currentBlock['name'] !== 'root'
    && (array_key_exists($currentBlock['name'], MasterController::$blockNames) === true
      && $currentBlock['index'] > MasterController::$blockNames[$currentBlock['name']])
  )
  {
    MasterController::$blockNames[$currentBlock['name']] = count(MasterController::$blocksStack);
  }

  array_push(MasterController::$blocksStack, $currentBlock);

  MasterController::$currentBlock = [
    'content' => '',
    'index' => $currentBlock['parent']['index'],
    'parent' => $currentBlock['parent']['parent'],
    'name' => $currentBlock['parent']['name']
  ];

  ob_start();
}

function parent()
{
  echo MasterController::$currentBlock['parent']['content'];
}
?>
