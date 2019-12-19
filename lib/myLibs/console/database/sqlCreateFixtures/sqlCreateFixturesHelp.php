<?php
return [
  'Generates fixtures sql files and executes them. (sql_generate_fixtures)',
  [
    'databaseName' => 'The database name !',
    'mask' => '1 => We erase the database' . PHP_EOL .
      str_repeat(' ', \lib\myLibs\console\TasksManager::$STRING_PAD_FOR_OPTION_FORMATTING) . '2 => We clean the fixtures sql files and we erase the database.'
  ],
  ['required', 'optional'],
  'Database'
];
