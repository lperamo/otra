<?php
declare(strict_types=1);
return [
  'Creates controllers.',
  [
    'bundle' => 'The bundle where you want to put controllers',
    'module' => 'The module where you want to put controllers',
    'controller' => 'The name of the controller!',
    'interactive' => 'If set to false, no question will be asked but the status messages are shown. Defaults to true.'
  ],
  [
    'required',
    'required',
    'required',
    'optional'
  ],
  'Architecture'
];
