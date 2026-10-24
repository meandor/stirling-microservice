# Stirling-Microservice
[![Latest Stable Version](https://poser.pugx.org/meandor/stirling-microservice/v/stable)](https://packagist.org/packages/meandor/stirling-microservice)
[![Total Downloads](https://poser.pugx.org/meandor/stirling-microservice/downloads)](https://packagist.org/packages/meandor/stirling-microservice)
[![Latest Unstable Version](https://poser.pugx.org/meandor/stirling-microservice/v/unstable)](https://packagist.org/packages/meandor/stirling-microservice)
[![License](https://poser.pugx.org/meandor/stirling-microservice/license)](https://packagist.org/packages/meandor/stirling-microservice)
[![Monthly Downloads](https://poser.pugx.org/meandor/stirling-microservice/d/monthly)](https://packagist.org/packages/meandor/stirling-microservice)
[![Daily Downloads](https://poser.pugx.org/meandor/stirling-microservice/d/daily)](https://packagist.org/packages/meandor/stirling-microservice)
[![composer.lock](https://poser.pugx.org/meandor/stirling-microservice/composerlock)](https://packagist.org/packages/meandor/stirling-microservice)
[![CI](https://github.com/meandor/stirling-microservice/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/meandor/stirling-microservice/actions/workflows/ci.yml)

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

To scan the code and its dependencies for vulnerabilities, secrets and
misconfigurations with [Trivy](https://trivy.dev/) (runs inside Docker,
no binary to install — the image is pulled automatically on first run):
````bash
./bin/go security-check
````

This scans `composer.lock` (including dev dependencies) for known
vulnerabilities. It is a dependency scan, not a scan of your PHP source
code.

To check the code style with PHP CS Fixer (validates, does not change files):
````bash
./bin/go lint-check
````

To auto-fix the code style with PHP CS Fixer:
````bash
./bin/go lint
````

To statically analyse the code with PHPStan (level 4, checks `src/`):
````bash
./bin/go static-analysis
````

To check that no method exceeds the configured cyclomatic complexity threshold
(uses PHPMD):
````bash
./bin/go complexity
````

To run lint-check, static-analysis, security-check, the complexity check and the
unit tests all at once:
````bash
./bin/go check
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
