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

interface MatomoExceptionInterface
{
    /**
     * Sets the API endpoint URL.
     *
     * @param UriInterface $uri the Matomo API endpoint URL
     *
     * @return self chainable method
     */
    public function setApi(UriInterface $uri): self;

    /**
     * Returns the API endpoint URL.
     *
     * @return UriInterface the Matomo API endpoint URL
     */
    public function getApi(): ?UriInterface;

    /**
     * @param array<string,string> $params API parameters for the request
     */
    public function setParams(array $params): self;

    /**
     * @return array<string,string>
     */
    public function getParams(): array;
}
