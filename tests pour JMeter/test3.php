<?php
declare(strict_types=1);
namespace web;

/**
 * @return array
 */
function test3() : array
{
  $variables = [];
  $variables['array'] = [
    'test',
    'test',
    'test',
    'test',
    'test',
    'test',
    'test',
    'test',
    'test',
    'test',
    'test',
    'test',
    'test',
    'test',
    'test',
    'test',
    'test',
    'test',
    'test',
    'test',
    'test',
    'test',
  ];
  $functions = [];
  /**
   * @param string $firstname
   * @param string $lastname
   *
   * @return void
   */
  $functions['coucou'] = function (string $firstname, string $lastname) use ($variables): void
  {
    echo 'Coucou ', $firstname, ' ', $lastname, '<br>';
    var_dump($variables['array']);
  };

  return $functions;
}

$test3 = test3();
$test3['coucou']('Lionel', 'Péramo');
