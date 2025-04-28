<?php

/**
 * tomkyle/matomo-api-client (https://github.com/tomkyle/matomo-api-client)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tomkyle\MatomoApiClient;

use Psr\Log;

class RetryingMatomoApiClient implements MatomoApiClientInterface
{
    use MatomoApiClientTrait;
    use Log\LoggerAwareTrait;

    public function __construct(MatomoApiClientInterface $matomoApiClient, protected int $max_attempts = 3, protected int $wait_seconds = 5, ?Log\LoggerInterface $logger = null)
    {
        $this->setMatomoClient($matomoApiClient);
        $this->setLogger($logger ?: new Log\NullLogger());
    }


    /**
     * @inheritDoc
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
                    $msg = sprintf("Requesting Matomo failed, retry in %s seconds…", $this->wait_seconds);
                    $this->logger->log(Log\LogLevel::NOTICE, $msg, $logger_context);
                    sleep($this->wait_seconds);
                    $attempt++;
                } else {
                    $msg = sprintf("Requesting Matomo failed after %s attempts.", $this->max_attempts);
                    $this->logger->log(Log\LogLevel::ERROR, $msg, $logger_context);
                    throw $matomoApiClientException;
                }
            }
        }

        throw new MatomoApiClientException("Too many attempts to request Matomo.");
    }
}
