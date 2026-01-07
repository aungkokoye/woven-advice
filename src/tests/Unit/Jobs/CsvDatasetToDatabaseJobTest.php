<?php
declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\CsvDatasetToDatabaseJob;
use App\Mail\CsvProcessingComplete;
use App\Services\CsvDatasetHandlerService;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\PendingMail;
use Mockery;
use PHPUnit\Framework\TestCase;

class CsvDatasetToDatabaseJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_job_calls_service_process_with_correct_path(): void
    {
        // Arrange
        $filePath = 'uploads/test-dataset.csv';
        $emailAddress = 'test@example.com';

        $mockService = Mockery::mock(CsvDatasetHandlerService::class);
        $mockService->shouldReceive('process')
            ->once()
            ->with($filePath)
            ->andReturn(true);

        $mockPendingMail = Mockery::mock(PendingMail::class);
        $mockPendingMail->shouldReceive('send')->once()->andReturn(null);

        $mockMailer = Mockery::mock('alias:' . \Illuminate\Support\Facades\Mail::class);
        $mockMailer->shouldReceive('to')->once()->andReturn($mockPendingMail);

        $job = new CsvDatasetToDatabaseJob($filePath, $emailAddress);

        // Act
        $job->handle($mockService);

        // Assert
        $mockService->shouldHaveReceived('process')->with($filePath)->once();
        $this->assertTrue(true); // Explicit assertion for PHPUnit
    }

    public function test_job_sends_email_to_correct_recipient(): void
    {
        // Arrange
        $filePath = 'uploads/test-dataset.csv';
        $emailAddress = 'test@example.com';

        $mockService = Mockery::mock(CsvDatasetHandlerService::class);
        $mockService->shouldReceive('process')
            ->once()
            ->with($filePath)
            ->andReturn(true);

        $mockPendingMail = Mockery::mock(PendingMail::class);
        $mockPendingMail->shouldReceive('send')
            ->once()
            ->with(Mockery::type(CsvProcessingComplete::class))
            ->andReturn(null);

        $mockMailer = Mockery::mock('alias:' . \Illuminate\Support\Facades\Mail::class);
        $mockMailer->shouldReceive('to')
            ->once()
            ->with($emailAddress)
            ->andReturn($mockPendingMail);

        $job = new CsvDatasetToDatabaseJob($filePath, $emailAddress);

        // Act
        $job->handle($mockService);

        // Assert
        $mockMailer->shouldHaveReceived('to')->with($emailAddress)->once();
        $this->assertTrue(true); // Explicit assertion for PHPUnit
    }

    public function test_job_sends_email_with_csv_processing_complete_mailable(): void
    {
        // Arrange
        $filePath = 'uploads/test-dataset.csv';
        $emailAddress = 'test@example.com';
        $mailableVerified = false;

        $mockService = Mockery::mock(CsvDatasetHandlerService::class);
        $mockService->shouldReceive('process')
            ->once()
            ->andReturn(true);

        $mockPendingMail = Mockery::mock(PendingMail::class);
        $mockPendingMail->shouldReceive('send')
            ->once()
            ->with(Mockery::on(function ($arg) use ($filePath, &$mailableVerified) {
                if (!$arg instanceof CsvProcessingComplete) {
                    return false;
                }

                // Verify the filePath using reflection
                $reflection = new \ReflectionClass($arg);
                $property = $reflection->getProperty('filePath');
                $property->setAccessible(true);

                $mailableVerified = $property->getValue($arg) === $filePath;
                return $mailableVerified;
            }))
            ->andReturn(null);

        $mockMailer = Mockery::mock('alias:' . \Illuminate\Support\Facades\Mail::class);
        $mockMailer->shouldReceive('to')
            ->once()
            ->andReturn($mockPendingMail);

        $job = new CsvDatasetToDatabaseJob($filePath, $emailAddress);

        // Act
        $job->handle($mockService);

        // Assert
        $mockPendingMail->shouldHaveReceived('send')->once();
        $this->assertTrue($mailableVerified, 'Mailable should contain correct file path');
    }

    public function test_job_executes_service_before_sending_email(): void
    {
        // Arrange
        $filePath = 'uploads/test-dataset.csv';
        $emailAddress = 'test@example.com';
        $executionOrder = [];

        $mockService = Mockery::mock(CsvDatasetHandlerService::class);
        $mockService->shouldReceive('process')
            ->once()
            ->with($filePath)
            ->andReturnUsing(function () use (&$executionOrder) {
                $executionOrder[] = 'service_process';
                return true;
            });

        $mockPendingMail = Mockery::mock(PendingMail::class);
        $mockPendingMail->shouldReceive('send')
            ->once()
            ->andReturnUsing(function () use (&$executionOrder) {
                $executionOrder[] = 'email_sent';
                return null;
            });

        $mockMailer = Mockery::mock('alias:' . \Illuminate\Support\Facades\Mail::class);
        $mockMailer->shouldReceive('to')
            ->once()
            ->andReturn($mockPendingMail);

        $job = new CsvDatasetToDatabaseJob($filePath, $emailAddress);

        // Act
        $job->handle($mockService);

        // Assert - service should be called before email is sent
        $this->assertEquals(['service_process', 'email_sent'], $executionOrder);
    }

    public function test_job_handles_different_file_paths(): void
    {
        // Arrange
        $testCases = [
            'uploads/dataset-2024.csv',
            'data/investors.csv',
            'temp/import-file.csv',
        ];

        $testCount = 0;

        foreach ($testCases as $filePath) {
            Mockery::close(); // Reset mocks between iterations

            $emailAddress = 'test@example.com';

            $mockService = Mockery::mock(CsvDatasetHandlerService::class);
            $mockService->shouldReceive('process')
                ->once()
                ->with($filePath)
                ->andReturn(true);

            $mockPendingMail = Mockery::mock(PendingMail::class);
            $mockPendingMail->shouldReceive('send')->once()->andReturn(null);

            $mockMailer = Mockery::mock('alias:' . \Illuminate\Support\Facades\Mail::class);
            $mockMailer->shouldReceive('to')->once()->andReturn($mockPendingMail);

            $job = new CsvDatasetToDatabaseJob($filePath, $emailAddress);

            // Act
            $job->handle($mockService);

            // Assert
            $mockService->shouldHaveReceived('process')->with($filePath)->once();
            $testCount++;
        }

        $this->assertEquals(count($testCases), $testCount, 'All test cases should be executed');
    }

    public function test_job_handles_different_email_addresses(): void
    {
        // Arrange
        $testCases = [
            'admin@example.com',
            'user@test.org',
            'processor@company.co.uk',
        ];

        $testCount = 0;

        foreach ($testCases as $emailAddress) {
            Mockery::close(); // Reset mocks between iterations

            $filePath = 'uploads/test.csv';

            $mockService = Mockery::mock(CsvDatasetHandlerService::class);
            $mockService->shouldReceive('process')
                ->once()
                ->andReturn(true);

            $mockPendingMail = Mockery::mock(PendingMail::class);
            $mockPendingMail->shouldReceive('send')->once()->andReturn(null);

            $mockMailer = Mockery::mock('alias:' . \Illuminate\Support\Facades\Mail::class);
            $mockMailer->shouldReceive('to')
                ->once()
                ->with($emailAddress)
                ->andReturn($mockPendingMail);

            $job = new CsvDatasetToDatabaseJob($filePath, $emailAddress);

            // Act
            $job->handle($mockService);

            // Assert
            $mockMailer->shouldHaveReceived('to')->with($emailAddress)->once();
            $testCount++;
        }

        $this->assertEquals(count($testCases), $testCount, 'All test cases should be executed');
    }

    public function test_job_propagates_service_exceptions(): void
    {
        // Arrange
        $filePath = 'uploads/test-dataset.csv';
        $emailAddress = 'test@example.com';
        $expectedException = new \RuntimeException('CSV processing failed');

        $mockService = Mockery::mock(CsvDatasetHandlerService::class);
        $mockService->shouldReceive('process')
            ->once()
            ->with($filePath)
            ->andThrow($expectedException);

        $job = new CsvDatasetToDatabaseJob($filePath, $emailAddress);

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CSV processing failed');

        // Act
        $job->handle($mockService);
    }

    public function test_job_does_not_send_email_if_service_throws_exception(): void
    {
        // Arrange
        $filePath = 'uploads/test-dataset.csv';
        $emailAddress = 'test@example.com';

        $mockService = Mockery::mock(CsvDatasetHandlerService::class);
        $mockService->shouldReceive('process')
            ->once()
            ->andThrow(new \RuntimeException('Processing error'));

        // Mock Mail facade - should NOT be called
        $mockMailer = Mockery::mock('alias:' . \Illuminate\Support\Facades\Mail::class);
        $mockMailer->shouldNotReceive('to');

        $job = new CsvDatasetToDatabaseJob($filePath, $emailAddress);

        // Act
        try {
            $job->handle($mockService);
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            // Expected exception
            $this->assertEquals('Processing error', $e->getMessage());
        }

        // Assert - Mail::to should not have been called
        $mockMailer->shouldNotHaveReceived('to');
    }

    public function test_job_constructor_stores_path_and_email(): void
    {
        // Arrange
        $filePath = 'uploads/test.csv';
        $emailAddress = 'test@example.com';

        // Act
        $job = new CsvDatasetToDatabaseJob($filePath, $emailAddress);

        // Assert - verify properties are set using reflection
        $reflection = new \ReflectionClass($job);

        $pathProperty = $reflection->getProperty('path');
        $pathProperty->setAccessible(true);
        $this->assertEquals($filePath, $pathProperty->getValue($job));

        $toProperty = $reflection->getProperty('to');
        $toProperty->setAccessible(true);
        $this->assertEquals($emailAddress, $toProperty->getValue($job));
    }

    public function test_job_implements_should_queue_interface(): void
    {
        // Assert
        $this->assertContains(
            'Illuminate\Contracts\Queue\ShouldQueue',
            class_implements(CsvDatasetToDatabaseJob::class)
        );
    }

    public function test_job_uses_queueable_trait(): void
    {
        // Assert
        $this->assertContains(
            'Illuminate\Foundation\Queue\Queueable',
            class_uses(CsvDatasetToDatabaseJob::class)
        );
    }
}