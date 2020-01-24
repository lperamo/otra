<?php

$showMaskOption = static function (string $text) : string
{
  return str_repeat(' ', \src\console\TasksManager::STRING_PAD_FOR_OPTION_FORMATTING) . $text;
};

return [
  'Creates a bundle.',
  [
    'bundle name' => 'The name of the bundle!',
    'mask' => 'In addition to the module, it will create a folder for :' . PHP_EOL .
      $showMaskOption('0 => nothing') . PHP_EOL .
      $showMaskOption('1 => config') . PHP_EOL .
      $showMaskOption('2 => models') . PHP_EOL .
      $showMaskOption('4 => resources') . PHP_EOL .
      $showMaskOption('8 => views')
  ],
  [
    'optional',
    'optional'
  ],
  'Architecture'
];
