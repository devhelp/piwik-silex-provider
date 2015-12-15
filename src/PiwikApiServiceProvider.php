<?php


namespace Devhelp\Silex\Piwik;

use Devhelp\Piwik\Api\Api;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Adds Api services to the container.
 */
class PiwikApiServiceProvider implements ServiceProviderInterface
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var Application
     */
    private $app;

    /**
     * example config:
     * array (
     *      "client" => "my_piwik.client" (service id)
     *      "api" => array(
     *          "reader" => "http://my_piwik_instance.piwik.pro",
     *          "default_params" => array(
     *              (can be either a service id or raw value)
     *              "token_auth" => "my_token_auth_param_service_id",
     *              "idSite" => 123
     *          )
     *      )
     * )
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function register(Application $app)
    {

    }

    public function boot(Application $app)
    {
        $this->app = $app;

        $this->addApis();
    }

    private function addApis()
    {
        $clientServiceId = $this->config['client'];
        $apiConfig =  $this->config['api'];
        $apiDefaultConfig = array(
            'url' => null,
            'default_params' => array()
        );

        $firstApiName = key($apiConfig);

        foreach ($apiConfig as $name => $config) {
            $config = array_merge($apiDefaultConfig, $config);
            $this->addApi($clientServiceId, $name, $config);
        }

        $this->addAliases($firstApiName);
    }

    private function addApi($clientServiceId, $name, $config)
    {
        if (!$config['url']) {
            throw new \InvalidArgumentException("'url' must be defined");
        }

        $app = $this->app;

        $apiCallable = function () use ($app, $clientServiceId, $config) {

            if (!isset($app[$clientServiceId])) {
                throw new \InvalidArgumentException("'$clientServiceId' service does not exists");
            }

            $url = $config['url'];
            $defaultParams = array();

            /**
             * resolve params to container services if applicable
             */
            foreach ($config['default_params'] as $param => $value) {
                $value = isset($app[$value]) ? $app[$value] : $value;
                $defaultParams[$param] = $value;
            }

            /**
             * create new Api
             */
            $api = new Api($app[$clientServiceId], $url);
            $api->setDefaultParams($defaultParams);

            return $api;
        };

        $this->app['devhelp_piwik.api.'.$name] = $this->app->share($apiCallable);
    }

    private function addAliases($aliasedApiName)
    {
        $app = $this->app;
        $aliasCallable = function () use ($app, $aliasedApiName) {
            return $app['devhelp_piwik.api.'.$aliasedApiName];
        };

        $this->app['devhelp_piwik.api'] = $this->app->share($aliasCallable);
        $this->app['devhelp_piwik.api.default'] = $this->app->share($aliasCallable);
    }
}
