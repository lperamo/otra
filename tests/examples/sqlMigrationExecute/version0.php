<?php
declare(strict_types=1);
namespace otra\migrations;

return [
  'version' => 0,
  'description' => 'My super description',
  'up' => function()
  {
    $queries = [];

    foreach([0, 1] as $index)
    {
      $queries[] = "INSERT INTO otra_migration_versions(`version`, `executed_at`, `execution_time`) VALUES ($index, $index, $index);";
    }

    return
      [
          'transaction' => true,
          'queries' => $queries
      ];
  },
  'down' => function()
  {
    $queries = [];

    foreach([0, 1] as $index)
      $queries[] = 'DELETE FROM otra_migration_versions WHERE `version` = ' . $index . ';';

    return
      [
        'transaction' => true,
        'queries' => $queries
      ];
  }
];
