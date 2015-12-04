[![Build Status](https://travis-ci.org/devhelp/piwik-silex-provider.svg?branch=master)](https://travis-ci.org/devhelp/piwik-silex-provider)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/devhelp/piwik-silex-provider/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/devhelp/piwik-silex-provider?branch=master)

## Installation

For more information please check [composer website](http://getcomposer.org).

```
$ composer require 'devhelp/piwik-silex-provider:dev-master'
```

## Purpose

Provides integration of [Piwik API](http://developer.piwik.org/api-reference/reporting-api) into [Silex](http://silex.sensiolabs.org). Adds services to the dependency injection container that allows to use Piwik API methods as services.
It uses [devhelp/piwik-api](http://github.com/devhelp/piwik-api) library - check its documentation for more advanced usage.

## Usage

### Register the provider

```php
$app = new Silex\Application();

$app->register(new Devhelp\Silex\Piwik\PiwikApiServiceProvider(array(
    'client' => 'my_piwik.client',
    'api' => array(
        'reader' => array(
            'url' => 'http://my_piwik_instance.piwik.pro',
            'default_params' => array(
                'token_auth' => 'piwik_token_auth',
                'idSite' => 123
            )
        )
    )
)));
```

### Create piwik client service that was set as 'client'

This example uses `PiwikGuzzleClient` class that is responsible for making http request to [Piwik](http://piwik.org).
You can include this extension by including [devhelp/piwik-api-guzzle](http://github.com/devhelp/piwik-api-guzzle) in your project

```php
//'guzzle' service must implement GuzzleHttp\ClientInterface
$app['my_piwik.client'] = $app->share(function () use ($app) {
    return new Devhelp\Piwik\Api\Guzzle\Client\PiwikGuzzleClient($app['guzzle']));
});
```

### Use API method in your use case

add service to the container

```php
$app['my_service'] = $app->share(function () use ($app) {
    return new Acme\DemoBundle\Service\MyService($app['devhelp_piwik.api']);
});
```

example service definition

```php
namespace Acme\DemoBundle\Service;


use Devhelp\Piwik\Api\Api;

class MyService
{

    /**
     * @var Api
     */
    private $piwikApi;

    public function __construct(Api $piwikApi)
    {
        $this->piwikApi = $piwikApi;
    }

    public function doSomething()
    {
        //...
        $this->piwikApi->getMethod('PiwikPlugin.pluginAction')->call();
        //...
    }
}
```

### Define API parameters resolved at runtime

You are allowed to set services as a params. If you do that then the service will be used to resolve the parameter
at runtime. For example have a service that would return `token_auth` of logged in user


```php
$app = new Silex\Application();

$app->register(new Devhelp\Silex\Piwik\PiwikApiServiceProvider(array(
    'client' => 'my_piwik.client',
    'api' => array(
        'reader' => array(
            'url' => 'http://my_piwik_instance.piwik.pro',
            'default_params' => array(
                'token_auth' => 'my_token_auth_provider',
                'idSite' => 123
            )
        )
    )
)));
```

`my_token_auth_provider` service definition (assumes that SecurityServiceProvider is registered)

```php
$app['my_token_auth_provider'] = $app->share(function () use ($app) {
    return new Acme\DemoBundle\Param\MyTokenAuthProvider($app['security.token_storage']);
});
```

`MyTokenAuthProvider` class definition (assumes that User class has getPiwikToken method)

```php
namespace Acme\DemoBundle\Param;

use Devhelp\Piwik\Api\Param\Param;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class MyTokenAuthProvider implements Param
{

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function value()
    {
        $token = $this->tokenStorage->getToken();

        return $token instanceof TokenInterface ? $token->getUser()->getPiwikToken() : null;
    }
}
```

### Define API methods as services

```php
$app['my_piwik_method'] = $app->share(function () use ($app) {
    return $app['devhelp_piwik.api']->getMethod('VisitFrequency.get');
});
```

## Feedback/Requests

Feel free to create an issue if you think that something is missing or needs fixing. Feedback is more than welcome!

## Credits

Brought to you by : [devhelp.pl](http://devhelp.pl)
