<?php
declare(strict_types=1);

use const otra\services\{OTRA_LABEL_SECURITY_SELF, OTRA_LABEL_SECURITY_STRICT_DYNAMIC};

return [
  'csp' => [
    'script-src' => OTRA_LABEL_SECURITY_STRICT_DYNAMIC,
    'style-src' => OTRA_LABEL_SECURITY_SELF
  ]
];
