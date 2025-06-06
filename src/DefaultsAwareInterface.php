<?php

/**
 * tomkyle/matomo-api-client (https://github.com/tomkyle/matomo-api-client)
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
     * @return array<string,string>
     */
    public function getDefaults(): array;

    /**
     * Sets default API parameters.
     *
     * @param array<string,string> $defaults
     */
    public function setDefaults(array $defaults): self;

    /**
     * Merges new API parameters into the existing defaults.
     *
     * @param array<string,string> $defaults
     */
    public function mergeDefaults(array $defaults): self;
}
