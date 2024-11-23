<?php
declare(strict_types=1);
namespace web;

/**
 *
 */
class Test2
{
  private array $array = [
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

  /**
   * @param string $firstname
   * @param string $lastname
   *
   * @return void
   */
  public function coucou(string $firstname, string $lastname): void
  {
    echo 'Coucou ', $firstname, ' ', $lastname, '<br>';
    var_dump($this->array);
  }
}

$test2 = new Test2();
$test2->coucou('Lionel', 'Péramo');
