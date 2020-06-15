<?php
return [
  'HelloWorld' =>
    [
      'dev' => [
        'csp' => [
          'script-src' => "'self'",
        ]
      ],
      'prod' => [
        'csp' => [
          'style-src' => "'self'"
        ],
        'featurePolicy' =>
          [
            'accelerometer' => "'none'",
            'ambient-light-sensor' => "'none'",
            'autoplay' => "'none'",
            'battery' => "'none'",
            'camera' => "'none'",
            'display-capture' => "'none'",
            'document-domain' => "'none'",
            'encrypted-media' => "'none'",
            'execution-while-not-rendered' => "'none'",
            'execution-while-out-of-viewport' => "'none'",
            'fullscreen' => "'none'",
            'geolocation' => "'none'",
            'gyroscope' => "'none'",
            'layout-animations' => "'self' https://calendly.com",
            'magnetometer' => "'none'",
            'microphone' => "'none'",
            'midi' => "'none'",
            'navigation-override' => "'none'",
            'payment' => "'none'",
            'picture-in-picture' => "'self'",
            'publickey-credentials-get' => "'none'",
            'sync-script' => "'none'",
            'sync-xhr' => "'none'",
            'usb' => "'none'",
            'vr' => "'none'",
            'wake-lock' => "'none'",
            'xr-spatial-tracking' => "'none'"
          ]
      ]
    ]
];
