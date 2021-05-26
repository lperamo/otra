<?php
declare(strict_types=1);
return [
  'HelloWorld' => [
    'chunks' => ['/helloworld', 'HelloWorld', 'frontend', 'index', 'HomeAction'],
    'resources' => [
      'test' => ['pages/HelloWorld/screen'],
      'print_css' => ['pages/HelloWorld/print'],
      'template' => true
    ]
  ]
];
