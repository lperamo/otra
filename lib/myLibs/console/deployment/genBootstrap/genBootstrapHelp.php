<?php
return [
  'Launch the genClassMap command and generates a file that contains all the necessary php files.',
  [
    'genClassmap' => 'If set to 0, it prevents the generation/override of the class mapping file.',
    'verbose' => 'If set to 1, we print all the main warnings when the task fails. Put 2 to get every warning.',
    'route' => 'The route for which you want to generate the micro bootstrap.'
  ],
  ['optional', 'optional', 'optional'],
  'Deployment'
];
