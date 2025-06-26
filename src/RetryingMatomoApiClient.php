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

class RetryingMatomoApiClient implements MatomoApiClientInterface, DefaultsAwareInterface
{
    use MatomoApiClientTrait;
    use Log\LoggerAwareTrait;

    /**
     * Constructs a retry proxy instance with configurable attempts and wait time.
     *
     * @param MatomoApiClientInterface $matomoApiClient the Matomo API client
     * @param int                      $max_attempts    maximum number of attempts
     * @param int                      $wait_seconds    seconds to wait between attempts
     * @param null|Log\LoggerInterface $logger          PSR-3 Logger, default: null
     */
    public function __construct(MatomoApiClientInterface $matomoApiClient, protected int $max_attempts = 3, protected int $wait_seconds = 5, ?Log\LoggerInterface $logger = null)
    {
        $this->setMatomoClient($matomoApiClient);
        $this->setLogger($logger ?: new Log\NullLogger());
    }

    /**
     * Sends a request to the Matomo API with retry logic.
     *
     * @param array<string,string> $params  API parameters for the request
     * @param null|string          $method  optional: Specific method to override the default API method
     * @param int                  $attempt current attempt number
     *
     * @return array<mixed,mixed> the API response decoded into an associative array
     *
     * @throws MatomoApiClientException if all attempts fail
     */
    #[\Override]
    public function request(array $params, ?string $method = null, int $attempt = 1): array
    {
        while ($attempt <= $this->max_attempts) {
            try {
                $matomo_client = $this->getMatomoClient();

                return $matomo_client->request($params, $method);
            } catch (MatomoApiClientException $matomoApiClientException) {
                $logger_context = array_merge($params, ['exception' => $matomoApiClientException->getMessage()]);

                if ($attempt < $this->max_attempts) {
                    $msg = sprintf('Requesting Matomo failed, retry in %s seconds…', $this->wait_seconds);
                    $this->logger->log(Log\LogLevel::NOTICE, $msg, $logger_context);
                    sleep($this->wait_seconds);
                    ++$attempt;
                } else {
                    $msg = sprintf('Requesting Matomo failed after %s attempts.', $this->max_attempts);
                    $this->logger->log(Log\LogLevel::ERROR, $msg, $logger_context);

                    throw $matomoApiClientException;
                }
            }
        }

        throw new MatomoApiClientException('Too many attempts to request Matomo.');
    }
}
