<?php
declare(strict_types=1);

use const otra\services\OTRA_LABEL_SECURITY_SELF;

return [
  'csp' => [
    'script-src' => OTRA_LABEL_SECURITY_SELF,
    'style-src' => OTRA_LABEL_SECURITY_SELF
  ]
];
