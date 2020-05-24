<?php
declare(strict_types=1);
require CORE_PATH . 'tools/cli.php';

  list($return) = cli('composer update otra/otra --no-cache --no-autoloader');

  if ($return !== 0)
    throw new \otra\OtraException('', 1, '', NULL, [], true);

