# Stirling-Microservice
[![Latest Stable Version](https://poser.pugx.org/meandor/stirling-microservice/v/stable)](https://packagist.org/packages/meandor/stirling-microservice)
[![Total Downloads](https://poser.pugx.org/meandor/stirling-microservice/downloads)](https://packagist.org/packages/meandor/stirling-microservice)
[![Latest Unstable Version](https://poser.pugx.org/meandor/stirling-microservice/v/unstable)](https://packagist.org/packages/meandor/stirling-microservice)
[![License](https://poser.pugx.org/meandor/stirling-microservice/license)](https://packagist.org/packages/meandor/stirling-microservice)
[![Monthly Downloads](https://poser.pugx.org/meandor/stirling-microservice/d/monthly)](https://packagist.org/packages/meandor/stirling-microservice)
[![Daily Downloads](https://poser.pugx.org/meandor/stirling-microservice/d/daily)](https://packagist.org/packages/meandor/stirling-microservice)
[![composer.lock](https://poser.pugx.org/meandor/stirling-microservice/composerlock)](https://packagist.org/packages/meandor/stirling-microservice)
[![Build Status](https://travis-ci.org/meandor/stirling-microservice.svg?branch=master)](https://travis-ci.org/meandor/stirling-microservice)

A PHP microservice named after the super famous inventor of the stirling engine.

It is not very effective, but its moving!

## Tools
The main script to do everything is
````bash
./bin/go
````

To see a list of arguments:
````bash
./bin/go help
````

To execute all tests:
````bash
./bin/go test
````

## Usage
Add this library as a composer dependency and require the composer
autoloader. In your index.php simply put:

````php
Router::init();

Router::run();
````
This will start the Router. If you want to add routes place this
in between the init and run:
 
````php
Router::add('info', function () {
    phpinfo();
});
````
This would add the php info page under `http://<location>/info`

## Config
You can define global config parameters by adding a file called ``default.json`` into a resources folder on your path root
(where your index.php file should be).

To access your config parameters use the Config class.

### Example
default.json content (placed in root folder):
````json
{
    "foo": "bar"
}
````

Calling
````php
use Stirling\Core\Config;

$config = Config::instance();
echo $config->foo
````
will output "bar"

You can pass another file name as a string into the static instance
method of Config if you want to use another config json file.

## Status
Under `http://<location>/status` you can take a look at the app status. It aggregates
registered status functions.

**!important!** By default the complete config will be exposed in the status page.
So it is generally advised to password protect the status page in production.
To do so just add a `maintenanceUser` and `maintenancePassword` with corresponding values
in the config json. This will add basic authentication to internal maintenance pages.
 
To register a new status:
````php
$status = AppStatus::instance();
$status->registerStatus("status 1", "Descriptive message for this status", function () {
    return true;
});
````
