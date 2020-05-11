<?php

use otra\console\Database;

Database::executeFile($argv[2], $argv[3] ?? null);
