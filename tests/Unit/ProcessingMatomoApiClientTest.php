<?php

/**
 * tomkyle/matomo-api-client (https://github.com/tomkyle/matomo-api-client)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use tomkyle\MatomoApiClient\MatomoApiClientInterface;
use tomkyle\MatomoApiClient\ProcessingMatomoApiClient;

class ProcessingMatomoApiClientTest extends TestCase
{
    private MatomoApiClientInterface $matomoApiClient;

    private LoggerInterface $logger;

    private ProcessingMatomoApiClient $processingMatomoApiClient;

    /**
     * Set up mock objects and the subject under test (SUT).
     */
    #[\Override]
    protected function setUp(): void
    {
        $this->matomoApiClient = $this->createMock(MatomoApiClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processingMatomoApiClient = new ProcessingMatomoApiClient($this->matomoApiClient, $this->logger);
    }

    /**
     * Tests the request method with no processors.
     */
    public function testRequestWithoutProcessors(): void
    {
        $params = ['param1' => 'value1'];
        $method = 'SomeMethod';
        $apiResponse = ['key' => 'apiValue'];

        $this->matomoApiClient
            ->expects($this->once())
            ->method('request')
            ->with($params, $method)
            ->willReturn($apiResponse);

        $result = $this->processingMatomoApiClient->request($params, $method);

        $this->assertSame($apiResponse, $result);
    }

    /**
     * Tests the request method with processors.
     */
    public function testRequestWithProcessors(): void
    {
        $params = ['param1' => 'value1'];
        $method = 'SomeMethod';
        $initialResponse = ['key' => 'apiValue'];
        $processedResponse1 = ['key' => 'processedValue1'];
        $processedResponse2 = ['key' => 'processedValue2'];

        $this->matomoApiClient
            ->expects($this->once())
            ->method('request')
            ->with($params, $method)
            ->willReturn($initialResponse);

        $processor1 = fn(array $response, array $params) => $processedResponse1;

        $processor2 = fn(array $response, array $params) => $processedResponse2;

        $this->processingMatomoApiClient->setProcessors([$processor1, $processor2]);

        $loggedMessages = [];
        $this->logger
            ->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function ($level, $message) use (&$loggedMessages) {
                $loggedMessages[] = ['level' => $level, 'message' => $message];
            });

        $result = $this->processingMatomoApiClient->request($params, $method);

        // Assertions on the final result
        $this->assertSame($processedResponse2, $result);

        // Assertions on the logs
        $this->assertCount(2, $loggedMessages);
        $this->assertSame('debug', $loggedMessages[0]['level']);
        $this->assertStringContainsString('Processing Matomo result using', $loggedMessages[0]['message']);
        $this->assertSame('debug', $loggedMessages[1]['level']);
        $this->assertStringContainsString('Processing Matomo result using', $loggedMessages[1]['message']);
    }

    /**
     * Tests that processors can be added individually.
     */
    public function testAddProcessor(): void
    {
        $processor1 = fn(array $response, array $params) => $response;

        $processor2 = fn(array $response, array $params) => $response;

        $this->processingMatomoApiClient->addProcessor($processor1);
        $this->processingMatomoApiClient->addProcessor($processor2);

        $this->assertCount(2, $this->processingMatomoApiClient->processors);
        $this->assertSame($processor1, $this->processingMatomoApiClient->processors[0]);
        $this->assertSame($processor2, $this->processingMatomoApiClient->processors[1]);
    }
}
