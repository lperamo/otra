<?php
/** Production deployment task
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace lib\myLibs\console;

$mode = (isset($argv[2]) === true) ? (int) $argv[2] : 0;
$verbose = (isset($argv[3]) === true) ? (int) $argv[3] : 0;

// Generation of the class mapping
if ($mode === 3)
  Tasks::genClassMap([null, null, $verbose]);

// Generation of php productions files for all the routes
if ($mode > 1)
  Tasks::genBootstrap([null, null, 0, $verbose]);

// Generation of resource productions files for all the routes
if ($mode > 0)
  Tasks::genAssets([]);

// Copy all needed files in a special folder

