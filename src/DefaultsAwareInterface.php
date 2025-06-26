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

interface DefaultsAwareInterface extends Log\LoggerAwareInterface
{
    /**
     * Gets default API parameters.
     *
     * @return array<string,mixed>
     */
    public function getDefaults(): array;

    /**
     * Sets default API parameters.
     *
     * @param array<string,mixed> $defaults
     */
    public function setDefaults(array $defaults): self;

    /**
     * Merges new API parameters into the existing defaults.
     *
     * @param array<string,mixed> $defaults
     */
    public function mergeDefaults(array $defaults): self;
}
