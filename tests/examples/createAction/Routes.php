<?php declare(strict_types=1);
namespace bundles\Test\config\routes;

/**
 * @return array{
 *   testTest: array{
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
    'testTest' => [
      'chunks' => ['/testTest', 'Test', 'test', 'test', 'TestAction'],
      'resources' => [
        'template' => true
      ]
    ]
  ];
}
