<?php
declare(strict_types=1);
namespace web;

/**
 *
 */
class Test
{
  private static array $array = [
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
  public static function coucou(string $firstname, string $lastname): void
  {
    echo 'Coucou ', $firstname, ' ', $lastname, '<br>';
    var_dump(self::$array);
  }
}

Test::coucou('Lionel', 'Péramo');
