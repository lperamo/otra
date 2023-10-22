<?php
declare(strict_types=1);

use const otra\services\{OTRA_LABEL_SECURITY_SELF, OTRA_LABEL_SECURITY_STRICT_DYNAMIC};

return [
  'csp' => [
    'script-src' => OTRA_LABEL_SECURITY_STRICT_DYNAMIC,
    'style-src' => OTRA_LABEL_SECURITY_SELF
  ],
  'permissionsPolicy' =>
  [
    'accelerometer' => '',
    'ambient-light-sensor' => '',
    'autoplay' => '',
    'battery' => '',
    'camera' => '',
    'display-capture' => '',
    'document-domain' => '',
    'encrypted-media' => '',
    'execution-while-not-rendered' => '',
    'execution-while-out-of-viewport' => '',
    'fullscreen' => '',
    'geolocation' => '',
    'gyroscope' => '',
    'layout-animations' => OTRA_LABEL_SECURITY_SELF,
    'magnetometer' => '',
    'microphone' => '',
    'midi' => '',
    'navigation-override' => '',
    'payment' => '',
    'picture-in-picture' => "'self'",
    'publickey-credentials-get' => '',
    'sync-script' => '',
    'sync-xhr' => '',
    'usb' => '',
    'wake-lock' => '',
    'xr-spatial-tracking' => ''
  ]
];
