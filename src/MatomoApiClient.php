<?php

/**
 * This file is part of tomkyle/matomo-api-client
 *
 * Client library for interacting with the Matomo API. Supports retry logic and PSR-6 caches.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tomkyle\MatomoApiClient;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log;

/**
 * Provides a client for interacting with the Matomo API.
 */
class MatomoApiClient implements MatomoApiClientInterface, DefaultsAwareInterface
{
    use Log\LoggerAwareTrait;

    /**
     * @var ClientInterface the HTTP client to use for requests
     */
    protected ClientInterface $httpClient;

    /**
     * @var RequestFactoryInterface the request factory to use for building HTTP requests
     */
    protected RequestFactoryInterface $requestFactory;

    /**
     * @var array<string,mixed> default parameters for Matomo API requests
     */
    protected array $defaults = [
        'module' => 'API',
        'format' => 'JSON',
    ];

    /**
     * Initializes the client with the API endpoint, default parameters, and optional HTTP client.
     *
     * @param UriInterface                 $uri            the Matomo API endpoint URL
     * @param array<string,mixed>          $defaults       default Matomo API request parameters
     * @param null|Log\LoggerInterface     $logger         optional PSR-3 Logger (defaults to NullLogger)
     * @param null|ClientInterface         $httpClient     optional PSR-18 client (defaults to Guzzle)
     * @param null|RequestFactoryInterface $requestFactory optional PSR-17 Request factory (defaults to Guzzle)
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
     * @param array<string,mixed> $params API parameters for the request
     * @param null|string         $method optional specific API method
     *
     * @return array<mixed,mixed> the decoded JSON response as an associative array
     *
     * @throws MatomoApiClientException on HTTP or decoding errors
     */
    #[\Override]
    public function request(array $params, ?string $method = null): array
    {
        $params = array_merge($this->defaults, $params, array_filter(['method' => $method]));
        $loggerContext = $params;
        unset($loggerContext['token_auth']);

        try {
            $this->logger->log(Log\LogLevel::DEBUG, 'Requesting Matomo API', $loggerContext);

            $uri = $this->uri->withQuery(http_build_query($params));
            $request = $this->requestFactory->createRequest('GET', $uri);
            $response = $this->httpClient->sendRequest($request);

            $result = $this->handleResponse($response, $loggerContext);

            $this->checkForErrors($result);

            return $result;
        } catch (\Throwable $throwable) {
            throw (new MatomoApiClientException('Matomo API request failed.', 0, $throwable))
                ->setApi($this->uri)
                ->setParams($params)
            ;
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
     * @return array<string,mixed> Default Matomo API request parameters
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * @param array<string,mixed> $defaults New default Matomo API request parameters
     */
    public function setDefaults(array $defaults): self
    {
        $this->defaults = $defaults;

        return $this;
    }

    /**
     * @param array<string,mixed> $defaults Additional default Matomo API request parameters
     */
    public function mergeDefaults(array $defaults): self
    {
        $this->defaults = array_merge($this->defaults, $defaults);

        return $this;
    }

    /**
     * @param array<mixed,mixed> $response
     *
     * @throws MatomoApiClientException
     */
    private function checkForErrors(array $response): void
    {
        if (isset($response['result']) && 'error' === $response['result']) {
            $msg = $response['message'] ?? '(no message)';

            throw new MatomoApiClientException($msg);
        }
    }

    /**
     * Handles and decodes the API response.
     *
     * @param ResponseInterface   $response the response to process
     * @param array<string,mixed> $context  context for logging
     *
     * @return array<mixed,mixed> decoded JSON response
     *
     * @throws MatomoApiClientException if decoding fails
     */
    private function handleResponse(ResponseInterface $response, array $context): array
    {
        try {
            $body = $response->getBody()->__toString();
            $this->logger->log(Log\LogLevel::DEBUG, 'Decoding Matomo API response', $context);
            $result = json_decode($body, associative: true, flags: \JSON_THROW_ON_ERROR);
            if (!is_array($result)) {
                throw new \UnexpectedValueException('Expected Matomo API response to be an array.');
            }

            return $result;
        } catch (\JsonException $jsonException) {
            throw (new MatomoApiClientException('Failed to decode the Matomo API response.', 0, $jsonException))
                ->setApi($this->uri)
                ->setParams($context)
            ;
        }
    }
}
