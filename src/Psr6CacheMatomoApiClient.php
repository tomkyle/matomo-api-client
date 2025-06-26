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

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log;

class Psr6CacheMatomoApiClient implements MatomoApiClientInterface, DefaultsAwareInterface
{
    use MatomoApiClientTrait;
    use Log\LoggerAwareTrait;

    /**
     * The cache pool instance for storing and retrieving cache items.
     *
     * @var CacheItemPoolInterface
     */
    protected $cache;

    /**
     * Constructs a new cache proxy instance.
     *
     * @param CacheItemPoolInterface   $cacheItemPool   the cache pool
     * @param MatomoApiClientInterface $matomoApiClient the Matomo API client
     * @param Log\LoggerInterface      $logger          PSR-3 Logger, default: null
     */
    public function __construct(CacheItemPoolInterface $cacheItemPool, MatomoApiClientInterface $matomoApiClient, ?Log\LoggerInterface $logger = null)
    {
        $this->setMatomoClient($matomoApiClient);
        $this->setCache($cacheItemPool);
        $this->setLogger($logger ?: new Log\NullLogger());
    }

    /**
     * Sends a request to the Matomo API with specified parameters, using cache when available.
     *
     * @param array<string,string> $params API parameters for the request
     * @param null|string          $method optional: Specific method to override the default API method
     *
     * @return array<mixed,mixed> the API response decoded into an associative array
     */
    #[\Override]
    public function request(array $params, ?string $method = null): array
    {
        // Generate a unique cache key based on the parameters and method
        $defaults = $this->getDefaults();
        $cacheParams = array_merge($defaults, $params);
        $cacheKey = $this->generateCacheKey($cacheParams, $method);

        // Attempt to retrieve the response from cache
        $this->getCache();
        $cacheItem = $this->cache->getItem($cacheKey);
        if ($cacheItem->isHit()) {
            $this->logger->log(Log\LogLevel::INFO, 'Matomo API response found in cache', ['cacheKey' => $cacheKey]);
            $result = $cacheItem->get();
            if (is_array($result)) {
                return $result;
            }

            throw new \UnexpectedValueException('Expected cached Matomo API response to be an array.');
        }

        // If not in cache, use the inner client to make a request
        $this->logger->log(Log\LogLevel::DEBUG, 'Matomo API response not found in cache, delegate to client', ['cacheKey' => $cacheKey]);
        $matomoApiClient = $this->getMatomoClient();
        $response = $matomoApiClient->request($params, $method);

        // Store the response in cache for future requests
        $cacheItem->set($response);
        $this->cache->save($cacheItem);
        $this->logger->log(Log\LogLevel::INFO, 'Matomo API response stored in cache', ['cacheKey' => $cacheKey]);

        return $response;
    }

    /**
     * Generates a deterministic cache key from the request parameters and method.
     *
     * @param array<string,mixed> $params API parameters for the request
     * @param null|string         $method the API method, if specified
     *
     * @return string a unique cache key for the request
     */
    public function generateCacheKey(array $params, ?string $method): string
    {
        ksort($params); // Sort the parameters to ensure the key is deterministic

        return md5(http_build_query($params).($method ?? ''));
    }

    /**
     * Sets the cache pool instance for storing and retrieving cache items.
     *
     * @param CacheItemPoolInterface $cacheItemPool the cache pool
     */
    public function setCache(CacheItemPoolInterface $cacheItemPool): self
    {
        $this->cache = $cacheItemPool;

        return $this;
    }

    /**
     * Gets the cache pool instance for storing and retrieving cache items.
     *
     * @return CacheItemPoolInterface the cache pool
     */
    public function getCache(): CacheItemPoolInterface
    {
        return $this->cache;
    }
}
