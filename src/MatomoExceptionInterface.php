<?php

/**
 * tomkyle/matomo-api-client (https://github.com/tomkyle/matomo-api-client)
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
     * @param UriInterface $uri The Matomo API endpoint URL.
     * @return self Chainable method.
     */
    public function setApi(UriInterface $uri): self;


    /**
     * Returns the API endpoint URL.
     *
     * @return UriInterface The Matomo API endpoint URL.
     */
    public function getApi(): ?UriInterface;


    /**
     * @param array<string,string> $params API parameters for the request.
     */
    public function setParams(array $params): self;

    /**
     * @return array<string,string>
     */
    public function getParams(): array;
}
