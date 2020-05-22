<?php
declare(strict_types=1);

$showMaskOption = static function (string $text) : string
{
  return STRING_PAD_FOR_OPTION_FORMATTING . $text;
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
      $showMaskOption('8 => views'),
    'interactive' => 'If set to false, no question will be asked but the status messages are shown. Defaults to true.'
  ],
  [
    'optional',
    'optional',
    'optional'
  ],
  'Architecture'
];
