<?php
declare(strict_types=1);
return [
  'csp' => [
    'style-src' => OTRA_LABEL_SECURITY_SELF
  ],
  'featurePolicy' =>
  [
    'accelerometer' => OTRA_LABEL_SECURITY_NONE,
    'ambient-light-sensor' => OTRA_LABEL_SECURITY_NONE,
    'autoplay' => OTRA_LABEL_SECURITY_NONE,
    'battery' => OTRA_LABEL_SECURITY_NONE,
    'camera' => OTRA_LABEL_SECURITY_NONE,
    'display-capture' => OTRA_LABEL_SECURITY_NONE,
    'document-domain' => OTRA_LABEL_SECURITY_NONE,
    'encrypted-media' => OTRA_LABEL_SECURITY_NONE,
    'execution-while-not-rendered' => OTRA_LABEL_SECURITY_NONE,
    'execution-while-out-of-viewport' => OTRA_LABEL_SECURITY_NONE,
    'fullscreen' => OTRA_LABEL_SECURITY_NONE,
    'geolocation' => OTRA_LABEL_SECURITY_NONE,
    'gyroscope' => OTRA_LABEL_SECURITY_NONE,
    'layout-animations' => OTRA_LABEL_SECURITY_SELF,
    'magnetometer' => OTRA_LABEL_SECURITY_NONE,
    'microphone' => OTRA_LABEL_SECURITY_NONE,
    'midi' => OTRA_LABEL_SECURITY_NONE,
    'navigation-override' => OTRA_LABEL_SECURITY_NONE,
    'payment' => OTRA_LABEL_SECURITY_NONE,
    'picture-in-picture' => "'self'",
    'publickey-credentials-get' => OTRA_LABEL_SECURITY_NONE,
    'sync-script' => OTRA_LABEL_SECURITY_NONE,
    'sync-xhr' => OTRA_LABEL_SECURITY_NONE,
    'usb' => OTRA_LABEL_SECURITY_NONE,
    'wake-lock' => OTRA_LABEL_SECURITY_NONE,
    'xr-spatial-tracking' => OTRA_LABEL_SECURITY_NONE
  ]
];
