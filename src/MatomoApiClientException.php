<?php

/**
 * tomkyle/matomo-api-client (https://github.com/tomkyle/matomo-api-client)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tomkyle\MatomoApiClient;

use Psr\Http\Message\UriInterface;

class MatomoApiClientException extends \RuntimeException implements MatomoExceptionInterface
{
    /**
     * @var array<string,string>
     */
    protected $params = [];


    /**
     * The URL endpoint for the Matomo API.
     *
     * @var UriInterface|null
     */
    protected $api;


    /**
     * @inheritDoc
     */
    #[\Override]
    public function getApi(): ?UriInterface
    {
        return $this->api;
    }


    /**
     * @inheritDoc
     */
    #[\Override]
    public function setApi(UriInterface $uri): self
    {
        $this->api = $uri;
        return $this;
    }


    /**
     * @param array<string,string> $params API parameters for the request.
     */
    #[\Override]
    public function setParams(array $params): self
    {
        $this->params = $params;
        return $this;
    }

    /**
     * @return array<string,string>
     */
    #[\Override]
    public function getParams(): array
    {
        return $this->params;
    }


}
