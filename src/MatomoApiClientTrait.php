<?php

/**
 * tomkyle/matomo-api-client (https://github.com/tomkyle/matomo-api-client)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tomkyle\MatomoApiClient;

use Psr\Cache\CacheItemPoolInterface;

trait MatomoApiClientTrait
{
    /**
     * The underlying Matomo API client instance.
     *
     * @var MatomoApiClientInterface
     */
    protected $matomo_client;


    /**
     * Sets the underlying Matomo API client instance.
     *
     * @param MatomoApiClientInterface $matomoApiClient The Matomo API client.
     */
    public function setMatomoClient(MatomoApiClientInterface $matomoApiClient): self
    {
        $this->matomo_client = $matomoApiClient;
        return $this;
    }

    /**
     * Gets the underlying Matomo API client instance.
     *
     * @return MatomoApiClientInterface The Matomo API client.
     */
    public function getMatomoClient(): MatomoApiClientInterface
    {
        return $this->matomo_client;
    }


    /**
     * @inheritDoc
     */
    public function getDefaults(): array
    {
        if ($this->matomo_client instanceOf DefaultsAwareInterface) {
            return $this->matomo_client->getDefaults();
        }
        return [];
    }

    /**
     * @inheritDoc
     */
    public function setDefaults(array $defaults): self
    {
        if ($this->matomo_client instanceOf DefaultsAwareInterface) {
            $this->matomo_client->setDefaults($defaults);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function mergeDefaults(array $defaults): self
    {
        if ($this->matomo_client instanceOf DefaultsAwareInterface) {
            $this->matomo_client->mergeDefaults($defaults);
        }
        return $this;
    }
}
