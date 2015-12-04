<?php


namespace Devhelp\Silex\Piwik;


use Silex\Application;

class PiwikApiServiceProviderTest extends \PHPUnit_Framework_TestCase
{

    private $app;
    private $myClient;
    private $myParam;
    private $config;

    protected function setUp()
    {
        $app = new Application();

        $myClient = $this->getMockBuilder('Devhelp\Piwik\Api\Client\PiwikClient')
            ->disableOriginalConstructor()
            ->getMock();
        $app['my_client'] = $myClient;

        $myParam = $this->getMockBuilder('Devhelp\Piwik\Api\Param\Param')
            ->disableOriginalConstructor()
            ->getMock();
        $app['my_param'] = $myParam;

        $config = array(
            'client' => 'my_client',
            'api' => array(
                'reader' => array(
                    'url' => 'http://example.piwik.pro',
                    'default_params' => array(
                        'token_auth' => 'my_param',
                        'idSite' => 1
                    )
                )
            ),
        );

        $this->app = $app;
        $this->myClient = $myClient;
        $this->myParam = $myParam;
        $this->config = $config;
    }

    /**
     * @test
     */
    public function it_adds_api_services_to_the_application()
    {
        $piwikApiProvider = new PiwikApiServiceProvider($this->config);
        $piwikApiProvider->boot($this->app);

        $this->assertInstanceOf('Devhelp\Piwik\Api\Api', $this->app['devhelp_piwik.api.reader']);
        $this->assertSame($this->app['devhelp_piwik.api.reader'], $this->app['devhelp_piwik.api.default']);
        $this->assertSame($this->app['devhelp_piwik.api.default'], $this->app['devhelp_piwik.api']);
        $this->assertSame($this->app['devhelp_piwik.api.reader']->getDefaultParams(), array(
            'token_auth' => $this->myParam,
            'idSite' => 1
        ));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_throws_exception_if_client_service_does_not_exists()
    {
        unset($this->app['my_client']);

        $piwikApiProvider = new PiwikApiServiceProvider($this->config);
        $piwikApiProvider->boot($this->app);

        $this->app['devhelp_piwik.api'];
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_throws_exception_if_api_url_is_not_provided()
    {
        unset($this->app['api']['reader']['url']);

        $piwikApiProvider = new PiwikApiServiceProvider($this->config);
        $piwikApiProvider->boot($this->app);

        $this->app['devhelp_piwik.api'];
    }
}
