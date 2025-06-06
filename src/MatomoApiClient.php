<?php

/**
 * tomkyle/matomo-api-client (https://github.com/tomkyle/matomo-api-client)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tomkyle\MatomoApiClient;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\HttpFactory;


/**
 * Provides a client for interacting with the Matomo API.
 */
class MatomoApiClient implements MatomoApiClientInterface
{
    use Log\LoggerAwareTrait;

    /**
     * @var ClientInterface The HTTP client to use for requests.
     */
    protected ClientInterface $httpClient;

    /**
     * @var RequestFactoryInterface The request factory to use for building HTTP requests.
     */
    protected RequestFactoryInterface $requestFactory;

    /**
     * @var array<string,string> Default parameters for Matomo API requests.
     */
    protected array $defaults = [
        'module' => 'API',
        'format' => 'JSON',
    ];

    /**
     * Initializes the client with the API endpoint, default parameters, and optional HTTP client.
     *
     * @param UriInterface $uri The Matomo API endpoint URL.
     * @param array<string,string> $defaults Default Matomo API request parameters.
     * @param Log\LoggerInterface|null $logger Optional PSR-3 Logger (defaults to NullLogger).
     * @param ClientInterface|null $httpClient Optional PSR-18 client (defaults to Guzzle).
     * @param RequestFactoryInterface|null $requestFactory Optional PSR-17 Request factory (defaults to Guzzle).
     */
    public function __construct(
        protected UriInterface $uri,
        array $defaults = [],
        ?Log\LoggerInterface $logger = null,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
    ) {
        $this->mergeDefaults($defaults);
        $this->setLogger($logger ?: new Log\NullLogger());
        $this->httpClient = $httpClient ?: new HttpClient();
        $this->requestFactory = $requestFactory ?: new HttpFactory();
    }


    /**
     * Sends a request to the Matomo API with specified parameters.
     *
     * @param array<string,string> $params API parameters for the request.
     * @param string|null $method Optional specific API method.
     * @return array<mixed,mixed> The decoded JSON response as an associative array.
     *
     * @throws MatomoApiClientException On HTTP or decoding errors.
     */
    #[\Override]
    public function request(array $params, ?string $method = null): array
    {
        $params = array_merge($this->defaults, $params, array_filter(['method' => $method]));
        $loggerContext = $params;
        unset($loggerContext['token_auth']);

        try {
            $this->logger->log(Log\LogLevel::DEBUG, "Requesting Matomo API", $loggerContext);

            $uri = $this->uri->withQuery(http_build_query($params));
            $request = $this->requestFactory->createRequest('GET', $uri);
            $response = $this->httpClient->sendRequest($request);

            $result = $this->handleResponse($response, $loggerContext);


            $this->checkForErrors($result);
            return $result;

        } catch (\Throwable $throwable) {
            throw (new MatomoApiClientException("Matomo API request failed.", 0, $throwable))
                ->setApi($this->uri)
                ->setParams($params);
        }
    }


    /**
     * @param array<mixed,mixed> $response
     * @throws MatomoApiClientException
     */
    private function checkForErrors(array $response): void
    {
        if (isset($response['result']) && $response['result'] === 'error') {
            $msg = $response['message'] ?? "(no message)";

            throw new MatomoApiClientException($msg);
        }
    }



    /**
     * Handles and decodes the API response.
     *
     * @param ResponseInterface $response The response to process.
     * @param array<string,string> $context Context for logging.
     * @return array<mixed,mixed> Decoded JSON response.
     *
     * @throws MatomoApiClientException If decoding fails.
     */
    private function handleResponse(ResponseInterface $response, array $context): array
    {
        try {
            $body = $response->getBody()->__toString();
            $this->logger->log(Log\LogLevel::DEBUG, "Decoding Matomo API response", $context);
            $result = json_decode($body, associative: true, flags: \JSON_THROW_ON_ERROR);
            if (!is_array($result)) {
                throw new \UnexpectedValueException("Expected Matomo API response to be an array.");
            }
            return $result;
        } catch (\JsonException $jsonException) {
            throw (new MatomoApiClientException("Failed to decode the Matomo API response.", 0, $jsonException))
                ->setApi($this->uri)
                ->setParams($context);
        }
    }

    /**
     * Gets the API endpoint URL.
     */
    public function getApi(): UriInterface
    {
        return $this->uri;
    }

    /**
     * Sets the API endpoint URL.
     */
    public function setApi(UriInterface $uri): self
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * Gets default API parameters.
     *
     * @return array<string,string>
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * Sets default API parameters.
     *
     * @param array<string,string> $defaults
     */
    public function setDefaults(array $defaults): self
    {
        $this->defaults = $defaults;
        return $this;
    }

    /**
     * Merges new parameters into the existing defaults.
     *
     * @param array<string,string> $defaults
     */
    public function mergeDefaults(array $defaults): self
    {
        $this->defaults = array_merge($this->defaults, $defaults);
        return $this;
    }
}
