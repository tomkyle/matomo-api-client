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

use Psr\Http\Message\UriInterface;

class MatomoApiClientException extends \RuntimeException implements MatomoExceptionInterface
{
    /**
     * @var array<string,mixed>
     */
    protected $params = [];

    /**
     * The URL endpoint for the Matomo API.
     *
     * @var null|UriInterface
     */
    protected $api;

    #[\Override]
    public function getApi(): ?UriInterface
    {
        return $this->api;
    }

    #[\Override]
    public function setApi(UriInterface $uri): self
    {
        $this->api = $uri;

        return $this;
    }

    /**
     * @param array<string,mixed> $params API parameters for the request
     */
    #[\Override]
    public function setParams(array $params): self
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @return array<string,mixed>
     */
    #[\Override]
    public function getParams(): array
    {
        return $this->params;
    }
}
