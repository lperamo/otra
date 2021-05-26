<?php
declare(strict_types=1);
return [
  'HelloWorld' => [
    'coucou' => ['/helloworld', 'HelloWorld', 'frontend', 'index', 'HomeAction'],
    'resources' => [
      '_css' => ['pages/HelloWorld/screen'],
      'print_css' => ['pages/HelloWorld/print'],
      'template' => true
    ]
  ]
];
