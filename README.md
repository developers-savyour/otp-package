## Add these lines to the composer.js file in your Laravel app

### "require": {
    #other packages
    "savyour/sms-and-email-package": "dev-master"
}

### "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/developers-savyour/otp-package.git"
        }
    ]


## Laravel configuration 

### Now publish its configuration 
```
php artisan vendor:publish
```
### Add this line into app.php
```
'OtpService' => Savyour\SmsAndEmailPackage\OtpService::class,
```

## Lumen configuration

### Add this code in bootstrap/app.php

```
$app->withFacades();
```
```
$app->configure('config-sms-and-email-package-service');
```

```
$app->register(\Savyour\SmsAndEmailPackage\SmsAndEmailPackageServiceProvider::class);
```