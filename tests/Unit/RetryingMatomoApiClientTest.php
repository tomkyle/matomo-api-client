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
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use tomkyle\MatomoApiClient\MatomoApiClientException;
use tomkyle\MatomoApiClient\MatomoApiClientInterface;
use tomkyle\MatomoApiClient\RetryingMatomoApiClient;

/**
 * @internal
 */
#[CoversNothing]
class RetryingMatomoApiClientTest extends TestCase
{
    private MatomoApiClientInterface $matomoApiClient;

    private LoggerInterface $logger;

    private RetryingMatomoApiClient $retryingMatomoApiClient;

    /**
     * Set up mock objects and the subject under test (SUT).
     */
    #[\Override]
    protected function setUp(): void
    {
        $this->matomoApiClient = $this->createMock(MatomoApiClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // Inject mocks into the SUT.
        $this->retryingMatomoApiClient = new RetryingMatomoApiClient($this->matomoApiClient, max_attempts: 3, wait_seconds: 0, logger: $this->logger);
    }

    /**
     * Tests that a successful request returns the expected result.
     */
    public function testRequestReturnsExpectedResult(): void
    {
        $params = ['param1' => 'value1'];
        $method = 'SomeMethod';
        $expectedResult = ['key' => 'value'];

        $this->matomoApiClient
            ->expects($this->once())
            ->method('request')
            ->with($params, $method)
            ->willReturn($expectedResult)
        ;

        $result = $this->retryingMatomoApiClient->request($params, $method);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests that retries are logged when an exception occurs.
     */
    public function testRetriesAreLoggedOnException(): void
    {
        $params = ['param1' => 'value1'];
        $method = 'SomeMethod';

        $this->matomoApiClient
            ->expects($this->exactly(3)) // 3 attempts
            ->method('request')
            ->with($params, $method)
            ->willThrowException(new MatomoApiClientException('Request failed.'))
        ;

        $logMessages = [];
        $this->logger
            ->expects($this->exactly(3)) // Logs for each retry
            ->method('log')
            ->willReturnCallback(function (string $level, string $message) use (&$logMessages) {
                $logMessages[] = [$level, $message];
            })
        ;

        $this->expectException(MatomoApiClientException::class);

        $this->retryingMatomoApiClient->request($params, $method);

        // Validate log messages
        $this->assertCount(2, $logMessages);
        $this->assertSame(LogLevel::NOTICE, $logMessages[0][0]);
        $this->assertStringContainsString('retry in', $logMessages[0][1]);

        $this->assertSame(LogLevel::ERROR, $logMessages[1][0]);
        $this->assertStringContainsString('failed after', $logMessages[1][1]);
    }

    /**
     * Tests that retries respect the configured retry count.
     * #[DataProvider('provideRetryData')].
     */
    #[DataProvider('provideRetryData')]
    public function testRetriesRespectRetryCount(int $attempts, int $expectedAttempts): void
    {
        $params = ['param1' => 'value1'];
        $method = 'SomeMethod';

        $this->retryingMatomoApiClient = new RetryingMatomoApiClient($this->matomoApiClient, max_attempts: $attempts, wait_seconds: 0, logger: $this->logger);

        $this->matomoApiClient
            ->expects($this->exactly($expectedAttempts))
            ->method('request')
            ->with($params, $method)
            ->willThrowException(new MatomoApiClientException('Request failed.'))
        ;

        $this->expectException(MatomoApiClientException::class);

        $this->retryingMatomoApiClient->request($params, $method);
    }

    /**
     * Data provider for testing retry counts.
     *
     * @return array<int, array<int>>
     */
    public static function provideRetryData(): array
    {
        return [
            'One attempt' => [1, 1],
            'Two attempts' => [2, 2],
            'Three attempts' => [3, 3],
        ];
    }
}
