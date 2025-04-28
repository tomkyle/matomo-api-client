<?php

/**
 * tomkyle/matomo-api-client (https://github.com/tomkyle/matomo-api-client)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tomkyle\MatomoApiClient;

use Psr\Log;

interface MatomoApiClientInterface extends Log\LoggerAwareInterface
{
    /**
     * Sends a request to the Matomo API with specified parameters.
     *
     * A second parameter 'method' may be passed to allow overdding API method.
     *
     * @param  array<string,string>  $params API params
     * @return array<mixed,mixed> API result
     */
    public function request(array $params, ?string $method = null): array;

}
