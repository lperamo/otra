[Home](../README.md) / HTTP Headers
                                
### Installation

You can configure the Content Security Policy and the Feature Policy in your actions.
Feature Policies have default values for the development environment, not for the production one.
You can override default values for development environment like so : 

```php
MasterController::$featurePolicy['dev']['sync-script'] = "'self'";
```

... and defining production Feature Policy like so :
```php
MasterController::$featurePolicy['prod'] = [
      'accelerometer' => "'none'",
      'ambient-light-sensor' => "'none'",
      'autoplay' => "'none'",
      'battery' => "'none'",
      'camera' => "'none'"
];
```

The configurations will be ***overwritten***.

You can define CSPs ***almost*** the same way :
```php
MasterController::$csp['dev'] =
    MasterController::$csp['prod'] = [
      'connect-src' => '\'self\' https://api.ssllabs.com https://hstspreload.org https://http-observatory.security.mozilla.org https://securityheaders.com https://sshscan.rubidus.com https://tls.imirhil.fr https://tls-observatory.services.mozilla.com https://www.immuniweb.com'
    ];
``` 

The configurations will be ***merged***.
