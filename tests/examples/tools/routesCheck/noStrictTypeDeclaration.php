<?php
return [
  'HelloWorld' => [
    'chunks' => ['/helloworld', 'HelloWorld', 'frontend', 'index', 'HomeAction'],
    'resources' => [
      '_css' => ['pages/HelloWorld/screen'],
      'print_css' => ['pages/HelloWorld/print'],
      'template' => true
    ]
  ]
];
