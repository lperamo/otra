<?php

use src\console\Database;

Database::clean(isset($argv[2]) ? '1' === $argv[2]  : false);
