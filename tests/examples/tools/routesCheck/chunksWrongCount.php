<?php
declare(strict_types=1);
return [
  'HelloWorld' => [
    'chunks' => ['/helloworld', 'HelloWorld', 'frontend', 'index'],
    'resources' => [
      'module_css' => ['pages/HelloWorld/screen'],
      'print_css' => ['pages/HelloWorld/print'],
      'template' => true
    ]
  ]
];
