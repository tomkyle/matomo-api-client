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

use Psr\Log;

class ProcessingMatomoApiClient implements MatomoApiClientInterface, DefaultsAwareInterface
{
    use MatomoApiClientTrait;
    use Log\LoggerAwareTrait;

    /**
     * @var callable[] an array to hold processor instances
     */
    public $processors = [];

    /**
     * Constructs a processing proxy instance.
     *
     * @param MatomoApiClientInterface $matomoApiClient the Matomo API client
     * @param null|Log\LoggerInterface $logger          PSR-3 Logger, default: null
     */
    public function __construct(MatomoApiClientInterface $matomoApiClient, ?Log\LoggerInterface $logger = null)
    {
        $this->setMatomoClient($matomoApiClient);
        $this->setLogger($logger ?: new Log\NullLogger());
    }

    /**
     * Sends a request to the Matomo API and processes the result.
     *
     * @param array<string,string> $params API parameters for the request
     * @param null|string          $method optional: Specific method to override the default API method
     *
     * @return array<mixed,mixed> the API response decoded into an associative array
     */
    #[\Override]
    public function request(array $params, ?string $method = null): array
    {
        $matomoApiClient = $this->getMatomoClient();
        $api_result = $matomoApiClient->request($params, $method);

        $loggerContextPamams = $params;
        unset($loggerContextPamams['token_auth']);

        foreach ($this->processors as $processor) {
            $msg = sprintf('Processing Matomo result using %s', get_debug_type($processor));
            $this->logger->log(Log\LogLevel::DEBUG, $msg, ['params' => $loggerContextPamams, 'result' => $api_result]);
            $api_result = $processor($api_result, $params);
        }

        return $api_result;
    }

    /**
     * Sets the processors.
     *
     * @param callable[] $processors
     */
    public function setProcessors(array $processors): self
    {
        $this->processors = $processors;

        return $this;
    }

    /**
     * Adds a processor.
     */
    public function addProcessor(callable $processor): self
    {
        $this->processors[] = $processor;

        return $this;
    }
}
