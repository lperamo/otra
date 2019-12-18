<?php

use lib\myLibs\console\Database;

Database::createDatabase(
  $argv[2],
  true === isset($argv[3])
    ? 'true' == $argv[3] // Forces the value to be a boolean
    : false
);
