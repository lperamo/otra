<?php
declare(strict_types=1);
namespace bundles\HelloWorld\config\routes;

/**
 * @return array{
 *   HelloWorld: array{
 *     chunks: list<string>,
 *     resources: array{
 *       module_css: list<string>,
 *       print_css: list<string>,
 *       template: bool
 *     }
 *   }
 * }
 */
function getRoutes(): array
{
  return [
    'HelloWorld' => [
      'chunks' => ['/helloworld', 'HelloWorld', 'frontend', 'index', 'HomeAction'],
      'resources' => [
        'module_css' => ['pages/HelloWorld/screen'],
        'print_css' => ['pages/HelloWorld/print'],
        'template' => true
      ]
    ]
  ];
}
