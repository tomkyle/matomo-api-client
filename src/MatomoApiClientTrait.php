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
     * @param MatomoApiClientInterface $matomoApiClient the Matomo API client
     */
    public function setMatomoClient(MatomoApiClientInterface $matomoApiClient): self
    {
        $this->matomo_client = $matomoApiClient;

        return $this;
    }

    /**
     * Gets the underlying Matomo API client instance.
     *
     * @return MatomoApiClientInterface the Matomo API client
     */
    public function getMatomoClient(): MatomoApiClientInterface
    {
        return $this->matomo_client;
    }

    /**
     * Gets default API parameters.
     *
     * @return array<string,mixed>
     */
    public function getDefaults(): array
    {
        if ($this->matomo_client instanceof DefaultsAwareInterface) {
            return $this->matomo_client->getDefaults();
        }

        return [];
    }

    /**
     * Sets default API parameters.
     *
     * @param array<string,mixed> $defaults
     */
    public function setDefaults(array $defaults): self
    {
        if ($this->matomo_client instanceof DefaultsAwareInterface) {
            $this->matomo_client->setDefaults($defaults);
        }

        return $this;
    }

    /**
     * Merges new API parameters into the existing defaults.
     *
     * @param array<string,mixed> $defaults
     */
    public function mergeDefaults(array $defaults): self
    {
        if ($this->matomo_client instanceof DefaultsAwareInterface) {
            $this->matomo_client->mergeDefaults($defaults);
        }

        return $this;
    }
}
