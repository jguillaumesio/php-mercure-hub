# :warning: Warning

**This project is still under development and is not usable yet, feel free to make
pull requests or to contact me for any further questions !**

# TODO

* Add config file checker
* Only store useful headers in Subscription manager request attribute

# Get started
## PHP Extensions
### Mandatory
* openssl
### Optional

Before starting it's greatly recommended to enabled these PHP extensions to improve  
UUID generation performances as ramsey/uuid prescribe

* ext-gmp
* ext-bcmath
## Configuration
Add the following key to you environment file, this must point out to the configuration  
file for the library
```env  
MERCURE_CONFIG_PATH=/path/to/config.php  
```  

Here is a default template for the configuration file, it must be a file that returns  
an associative array

```php  
<?php  
return [  
 'utils' => '\Namespace\To\UtilClass',
 'auth_cookie_name' => 'mercureAuthorization',
 'jwt' => [
	 'algo' => 'HS256',
	 'secret' => 'secret'
	 ]
 ]  
```  
You can either use the default utils class, or a custom one. If you implement your own  
utils class it must extends *\JGuillaumesio\PHPMercureHub\Utils\AbstractUtils* implements *\JGuillaumesio\PHPMercureHub\Utils\UtilsInterface*

For those who aren't familiar with JWT algorithm, here is a simple website that can help you to generate secret file in the algorithm you want

[8gwifi.org](https://8gwifi.org/jwsgen.jsp)
# Run tests
## Windows
```console
.\vendor\bin\phpunit --configuration phpunit.xml
```

## Others
```console
./vendor/bin/phpunit --configuration phpunit.xml
```