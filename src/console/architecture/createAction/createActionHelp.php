<?php
return [
  'Creates actions.',
  [
    'bundle' => 'The bundle where you want to put actions',
    'module' => 'The module where you want to put actions',
    'controller' => 'The controller where you want to put actions',
    'action' => 'The name of the action!',
    'interactive' => 'If set to false, no question will be asked but the status messages are shown. Defaults to true.'
  ],
  [
    'required',
    'required',
    'required',
    'required',
    'optional'
  ],
  'Architecture'
];
