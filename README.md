# SARA: Dynamic licensing system with PHP

## Information about the system
This license system allows you to dynamically create a license with Domain, IP address and Time.

The database is provided with sqllite3 It allows you to create and use as many remote licenses as you want by encrypting your files once in all your projects.

## Installation
Minimum PHP 5.6 is required for this system to work.

This is not a package. You need to fit your MVC Structure in accordance with the instructions I will give.

First of all, you need to give the following 2 parameters in the main directory.

```PHP
define('LICANSE_CODE', 'TFpsc2lkem9wMGxUOW5heEV5ZGN2RTZFbVNOOHJyZTJDTWMrbE5iY0tyZz0=');
define('LICANSE_DIR_PATH', __DIR__ . "/license.json");
```

These constant values ​​keep the path to your customer's license code and license file.

- `LICANSE_CODE` Customer-Specific License Code.
- `LICANSE_DIR_PATH` Path to Created and Read license file

You can leave these 2 values ​​open to the client. You will not need to encrypt.

You should add the codes in app.php to a file where your project will not work without that file on your system.

Do not forget to configure the specified values ​​according to yourself.
```PHP
    $get_url = 'https://www.arcface.net/dynamic_license/license.php';
```
It is the hosting path where your license.php file will reside. Replace this with the server address you are going to license check.

```PHP
    # license.php
   define('LICANSE_CRYPTO_KEY', '2c6326b1d378cb3555e5ee051302eb7e');
    # app.php
    $license['crypto_key'] = license_cypto_dec($license['crypto_key'],"2c6326b1d378cb3555e5ee051302eb7e");
```
`2c6326b1d378cb3555e5ee051302eb7e` don't forget to change this value.

## Final Step

Finally, transfer the `license.php` and `license.db` file to your hosting.

