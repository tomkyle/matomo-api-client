<?php

/**
 * This file is part of tomkyle/matomo-api-client
 *
 * Client library for interacting with the Matomo API. Supports retry logic and PSR-6 caches.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use tomkyle\MatomoApiClient\MatomoApiClientInterface;
use tomkyle\MatomoApiClient\Psr6CacheMatomoApiClient;

/**
 * @internal
 */
#[CoversNothing]
class Psr6CacheMatomoApiClientTest extends TestCase
{
    private CacheItemPoolInterface $cacheItemPool;

    private CacheItemInterface $cacheItem;

    private MatomoApiClientInterface $matomoApiClient;

    private LoggerInterface $logger;

    private Psr6CacheMatomoApiClient $psr6CacheMatomoApiClient;

    /**
     * Set up mock objects and the subject under test (SUT).
     */
    #[\Override]
    protected function setUp(): void
    {
        $this->cacheItemPool = $this->createMock(CacheItemPoolInterface::class);
        $this->cacheItem = $this->createMock(CacheItemInterface::class);
        $this->matomoApiClient = $this->createMock(MatomoApiClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->psr6CacheMatomoApiClient = new Psr6CacheMatomoApiClient(
            $this->cacheItemPool,
            $this->matomoApiClient,
            $this->logger,
        );
    }

    /**
     * Tests that a cached response is returned when available.
     */
    public function testRequestReturnsCachedResponse(): void
    {
        $params = ['param1' => 'value1'];
        $method = 'SomeMethod';
        $cacheKey = md5(http_build_query($params).$method);
        $cachedResponse = ['key' => 'cachedValue'];

        $this->cacheItemPool
            ->expects($this->once())
            ->method('getItem')
            ->with($cacheKey)
            ->willReturn($this->cacheItem)
        ;

        $this->cacheItem
            ->expects($this->once())
            ->method('isHit')
            ->willReturn(true)
        ;

        $this->cacheItem
            ->expects($this->once())
            ->method('get')
            ->willReturn($cachedResponse)
        ;

        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with($this->equalTo('info'), $this->stringContains('Matomo API response found in cache'))
        ;

        $result = $this->psr6CacheMatomoApiClient->request($params, $method);

        $this->assertSame($cachedResponse, $result);
    }

    public function testRequestFetchesFromClientAndCachesResponse(): void
    {
        $params = ['param1' => 'value1'];
        $method = 'SomeMethod';
        $cacheKey = md5(http_build_query($params).$method);
        $apiResponse = ['key' => 'apiValue'];

        $loggedMessages = [];

        $this->cacheItemPool
            ->expects($this->once())
            ->method('getItem')
            ->with($cacheKey)
            ->willReturn($this->cacheItem)
        ;

        $this->cacheItem
            ->expects($this->once())
            ->method('isHit')
            ->willReturn(false)
        ;

        $this->matomoApiClient
            ->expects($this->once())
            ->method('request')
            ->with($params, $method)
            ->willReturn($apiResponse)
        ;

        $this->cacheItem
            ->expects($this->once())
            ->method('set')
            ->with($apiResponse)
        ;

        $this->cacheItemPool
            ->expects($this->once())
            ->method('save')
            ->with($this->cacheItem)
        ;

        $this->logger
            ->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function ($level, $message) use (&$loggedMessages) {
                $loggedMessages[] = ['level' => $level, 'message' => $message];
            })
        ;

        $result = $this->psr6CacheMatomoApiClient->request($params, $method);

        // Assertions on the result
        $this->assertSame($apiResponse, $result);

        // Assertions on the logs
        $this->assertCount(2, $loggedMessages);
        $this->assertSame('debug', $loggedMessages[0]['level']);
        $this->assertStringContainsString('Matomo API response not found in cache', $loggedMessages[0]['message']);
        $this->assertSame('info', $loggedMessages[1]['level']);
        $this->assertStringContainsString('Matomo API response stored in cache', $loggedMessages[1]['message']);
    }

    /**
     * Tests the generation of deterministic cache keys.
     * #[DataProvider('provideCacheKeyData')].
     */
    #[DataProvider('provideCacheKeyData')]
    public function testGenerateCacheKey(array $params, ?string $method, string $expectedKey): void
    {
        $result = $this->psr6CacheMatomoApiClient->generateCacheKey($params, $method);

        $this->assertSame($expectedKey, $result, 'Cache key should match the expected deterministic result.');
    }

    /**
     * Data provider for testing cache key generation.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function provideCacheKeyData(): array
    {
        return [
            'Simple params with method' => [
                ['param1' => 'value1'],
                'methodName',
                md5(http_build_query(['param1' => 'value1']).'methodName'),
            ],
            'Sorted params' => [
                ['b' => '2', 'a' => '1'],
                'methodName',
                md5(http_build_query(['a' => '1', 'b' => '2']).'methodName'),
            ],
            'No method' => [
                ['param1' => 'value1'],
                null,
                md5(http_build_query(['param1' => 'value1']).''),
            ],
            'Empty params and null method' => [
                [],
                null,
                md5(''),
            ],
        ];
    }
}
