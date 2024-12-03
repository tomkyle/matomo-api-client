<?php

/**
 * tomkyle/matomo-api-client (https://github.com/tomkyle/matomo-api-client)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\NullLogger;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\HttpFactory;
use tomkyle\MatomoApiClient\MatomoApiClient;
use tomkyle\MatomoApiClient\MatomoApiClientException;

class MatomoApiClientTest extends TestCase
{
    private $api;

    private $defaults;

    private $requestFactory;

    private $logger;

    #[\Override]
    protected function setUp(): void
    {
        $this->api = new Uri('https://example.com/matomo');
        $this->defaults = ['token_auth' => 'test_token'];
        $this->logger = new NullLogger();
        $this->requestFactory = new HttpFactory();
    }

    /**
     * Test setting and getting API endpoint.
     */
    public function testSetAndGetApi()
    {
        $matomoApiClient = new MatomoApiClient($this->api);
        $uri = new Uri('https://example.com/new_matomo');
        $matomoApiClient->setApi($uri);

        $this->assertEquals($uri, $matomoApiClient->getApi());
    }

    /**
     * Test setting and getting default parameters.
     */
    public function testSetAndGetDefaults()
    {
        $matomoApiClient = new MatomoApiClient($this->api);

        $newDefaults = ['module' => 'API', 'format' => 'xml'];
        $matomoApiClient->setDefaults($newDefaults);

        $this->assertEquals($newDefaults, $matomoApiClient->getDefaults());
    }

    /**
     * Test merging default parameters.
     */
    public function testMergeDefaults()
    {
        $matomoApiClient = new MatomoApiClient($this->api, $this->defaults);

        $mergeDefaults = ['idSite' => '1'];
        $matomoApiClient->mergeDefaults($mergeDefaults);

        $expected = array_merge($this->defaults, $mergeDefaults);
        $this->assertEquals($expected, $matomoApiClient->getDefaults());
    }

    /**
     * Test sending a request to the Matomo API.
     */
    public function testRequestSuccess()
    {
        $mockHandler = new MockHandler([
            new Response(200, [], '{"result": "success"}'),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $matomoApiClient = new MatomoApiClient($this->api, $this->defaults, $this->logger, $httpClient, $this->requestFactory);

        $params = ['idSite' => '1', 'date' => 'today'];
        $result = $matomoApiClient->request($params);

        $this->assertEquals(['result' => 'success'], $result);
    }

    /**
     * Test handling HTTP error during request.
     */
    public function testRequestHttpError()
    {
        $mockHandler = new MockHandler([
            new Response(404),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $matomoApiClient = new MatomoApiClient($this->api, $this->defaults, $this->logger, $httpClient, $this->requestFactory);

        $params = ['idSite' => '1', 'date' => 'today'];

        $this->expectException(MatomoApiClientException::class);

        $matomoApiClient->request($params);
    }

    /**
     * Test handling cURL error during request.
     */
    public function testRequestCurlError()
    {
        $mockHandler = new MockHandler([
            new RequestException('cURL error message', new \GuzzleHttp\Psr7\Request('GET', 'test')),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $matomoApiClient = new MatomoApiClient($this->api, $this->defaults, $this->logger, $httpClient, $this->requestFactory);

        $params = ['idSite' => '1', 'date' => 'today'];

        $this->expectException(MatomoApiClientException::class);

        $matomoApiClient->request($params);
    }

    /**
     * Test handling JSON decoding error during response handling.
     */
    public function testHandleResponseJsonError()
    {
        $mockHandler = new MockHandler([
            new Response(200, [], 'invalid json'),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $matomoApiClient = new MatomoApiClient($this->api, $this->defaults, $this->logger, $httpClient, $this->requestFactory);

        $params = ['idSite' => '1', 'date' => 'today'];

        $this->expectException(MatomoApiClientException::class);

        $matomoApiClient->request($params);
    }

    /**
     * Test handling API error response.
     */
    public function testHandleApiErrorResponse()
    {
        $mockHandler = new MockHandler([
            new Response(200, [], '{"result": "error", "message": "An error occurred"}'),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $matomoApiClient = new MatomoApiClient($this->api, $this->defaults, $this->logger, $httpClient, $this->requestFactory);

        $params = ['idSite' => '1', 'date' => 'today'];

        $this->expectException(MatomoApiClientException::class);
        $this->expectExceptionMessage('Matomo API request failed.');

        $matomoApiClient->request($params);
    }
}
