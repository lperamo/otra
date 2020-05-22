<?php
declare(strict_types=1);
return [
  'Generates a server configuration adapted to OTRA.',
  [
    'file name' => 'Name of the file to put the generated configuration',
    'environment' => CLI_LIGHT_CYAN . 'dev' . CLI_CYAN . ' (default) or ' . CLI_LIGHT_CYAN . 'prod',
    'serverTechnology' => CLI_LIGHT_CYAN . 'nginx' . CLI_CYAN . ' (default) or ' . CLI_LIGHT_CYAN . 'apache' . CLI_YELLOW .
      ' (but works only for Nginx for now)'
  ],
  ['required', 'optional', 'optional'],
  'Deployment'
];

