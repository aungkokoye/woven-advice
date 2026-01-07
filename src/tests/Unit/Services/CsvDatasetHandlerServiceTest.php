<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Repositories\InvestmentRepository;
use App\Repositories\InvestorRepository;
use App\Services\CsvDatasetHandlerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class CsvDatasetHandlerServiceTest extends TestCase
{
    private string $tempDir;
    private CsvDatasetHandlerService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a temporary directory for test files
        $this->tempDir = sys_get_temp_dir() . '/csv_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
        mkdir($this->tempDir . '/app/private', 0777, true);

        $this->service = new CsvDatasetHandlerService();
    }

    protected function tearDown(): void
    {
        // Clean up temporary files
        $this->removeDirectory($this->tempDir);

        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (!file_exists($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function test_process_successfully_processes_valid_csv_file(): void
    {
        // Arrange
        $csvContent = "investor_id,name,age,investment_amount,investment_date\n" .
                      "1,John Doe,30,1000.50,01-01-2024\n" .
                      "2,Jane Smith,25,2000.75,02-01-2024\n";

        $filePath = 'test-dataset.csv';
        $this->createVirtualCsvFile('app/private/' . $filePath, $csvContent);

        $this->mockDatabaseTransaction();
        $this->mockRepositories();
        $this->mockLogging();

        // Override storage_path to use virtual file system
        $this->mockStoragePath();

        // Act
        $result = $this->service->process($filePath);

        // Assert
        $this->assertTrue($result);
    }

    public function test_process_returns_false_when_file_does_not_exist(): void
    {
        // Arrange
        $filePath = 'non-existent.csv';

        $mockLog = Mockery::mock('alias:' . Log::class);
        $mockLog->shouldReceive('info')->once();
        $mockLog->shouldReceive('error')
            ->once()
            ->with(Mockery::pattern('/Investor Dataset Processing:.*(Failed to open|No such file)/'));

        $this->mockStoragePath();

        // Act
        $result = $this->service->process($filePath);

        // Assert
        $this->assertFalse($result);
    }

    public function test_process_skips_rows_with_insufficient_columns(): void
    {
        // Arrange
        $csvContent = "investor_id,name,age,investment_amount,investment_date\n" .
                      "1,John Doe,30,1000.50,01-01-2024\n" .
                      "2,Jane\n" .  // Insufficient columns
                      "3,Bob Smith,35,3000.00,03-01-2024\n";

        $filePath = 'test-dataset.csv';
        $this->createVirtualCsvFile('app/private/' . $filePath, $csvContent);

        $this->mockDatabaseTransaction();
        $this->mockRepositories();

        $mockLog = Mockery::mock('alias:' . Log::class);
        $mockLog->shouldReceive('info')->twice(); // Start and complete
        $mockLog->shouldReceive('warning')
            ->once()
            ->with(Mockery::pattern('/skipping row 3.*insufficient columns/'));

        $this->mockStoragePath();

        // Act
        $result = $this->service->process($filePath);

        // Assert
        $this->assertTrue($result);
        $mockLog->shouldHaveReceived('warning')->once();
    }

    public function test_process_handles_large_batches_correctly(): void
    {
        // Arrange - Create CSV with 2500 rows (more than 2 batches)
        $header = "investor_id,name,age,investment_amount,investment_date\n";
        $rows = [];
        for ($i = 1; $i <= 2500; $i++) {
            $rows[] = "{$i},Investor {$i},30,1000.00,01-01-2024";
        }
        $csvContent = $header . implode("\n", $rows);

        $filePath = 'large-dataset.csv';
        $this->createVirtualCsvFile('app/private/' . $filePath, $csvContent);

        $transactionCount = 0;
        $mockDB = Mockery::mock('alias:' . DB::class);
        $mockDB->shouldReceive('transaction')
            ->times(3) // 1000 + 1000 + 500
            ->andReturnUsing(function ($callback) use (&$transactionCount) {
                $transactionCount++;
                $callback();
            });

        $this->mockRepositories();

        $mockLog = Mockery::mock('alias:' . Log::class);
        $mockLog->shouldReceive('info')->atLeast()->once(); // Start, batch progress, and complete

        $this->mockStoragePath();

        // Act
        $result = $this->service->process($filePath);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals(3, $transactionCount);
    }

    public function test_process_handles_exception_and_returns_false(): void
    {
        // Arrange
        $csvContent = "investor_id,name,age,investment_amount,investment_date\n" .
                      "1,John Doe,30,1000.50,invalid-date\n"; // Invalid date will cause exception

        $filePath = 'test-dataset.csv';
        $this->createVirtualCsvFile('app/private/' . $filePath, $csvContent);

        $mockLog = Mockery::mock('alias:' . Log::class);
        $mockLog->shouldReceive('info')->once();
        $mockLog->shouldReceive('error')
            ->once()
            ->with(Mockery::pattern('/Investor Dataset Processing:/'));

        $this->mockStoragePath();

        // Act
        $result = $this->service->process($filePath);

        // Assert
        $this->assertFalse($result);
    }

    public function test_process_logs_start_and_completion(): void
    {
        // Arrange
        $csvContent = "investor_id,name,age,investment_amount,investment_date\n" .
                      "1,John Doe,30,1000.50,01-01-2024\n";

        $filePath = 'test-dataset.csv';
        $this->createVirtualCsvFile('app/private/' . $filePath, $csvContent);

        $this->mockDatabaseTransaction();
        $this->mockRepositories();

        $logMessages = [];
        $mockLog = Mockery::mock('alias:' . Log::class);
        $mockLog->shouldReceive('info')
            ->andReturnUsing(function ($message) use (&$logMessages) {
                $logMessages[] = $message;
            });

        $this->mockStoragePath();

        // Act
        $this->service->process($filePath);

        // Assert
        $this->assertGreaterThanOrEqual(2, count($logMessages));
        $this->assertStringContainsString('Starting CSV processing', $logMessages[0]);
        $this->assertStringContainsString('completed', $logMessages[count($logMessages) - 1]);
    }

    public function test_process_formats_investment_amount_correctly(): void
    {
        // Arrange
        $csvContent = "investor_id,name,age,investment_amount,investment_date\n" .
                      "1,John Doe,30,1000.5,01-01-2024\n";

        $filePath = 'test-dataset.csv';
        $this->createVirtualCsvFile('app/private/' . $filePath, $csvContent);

        $mockDB = Mockery::mock('alias:' . DB::class);
        $mockDB->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                $callback();
            });

        $investorRepo = Mockery::mock(InvestorRepository::class);
        $investorRepo->shouldReceive('upsert')->once();

        $investmentData = [];
        $investmentRepo = Mockery::mock(InvestmentRepository::class);
        $investmentRepo->shouldReceive('insert')
            ->once()
            ->with(Mockery::on(function ($data) use (&$investmentData) {
                $investmentData = $data;
                return true;
            }));

        $this->mockAppFunction($investorRepo, $investmentRepo);
        $this->mockLogging();
        $this->mockStoragePath();

        // Act
        $this->service->process($filePath);

        // Assert
        $this->assertNotEmpty($investmentData);
        $this->assertEquals('1000.50', $investmentData[0]['investment_amount']);
    }

    public function test_process_converts_date_format_correctly(): void
    {
        // Arrange
        $csvContent = "investor_id,name,age,investment_amount,investment_date\n" .
                      "1,John Doe,30,1000.00,15-03-2024\n";

        $filePath = 'test-dataset.csv';
        $this->createVirtualCsvFile('app/private/' . $filePath, $csvContent);

        $mockDB = Mockery::mock('alias:' . DB::class);
        $mockDB->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                $callback();
            });

        $investorRepo = Mockery::mock(InvestorRepository::class);
        $investorRepo->shouldReceive('upsert')->once();

        $investmentData = [];
        $investmentRepo = Mockery::mock(InvestmentRepository::class);
        $investmentRepo->shouldReceive('insert')
            ->once()
            ->with(Mockery::on(function ($data) use (&$investmentData) {
                $investmentData = $data;
                return true;
            }));

        $this->mockAppFunction($investorRepo, $investmentRepo);
        $this->mockLogging();
        $this->mockStoragePath();

        // Act
        $this->service->process($filePath);

        // Assert
        $this->assertNotEmpty($investmentData);
        $this->assertEquals('2024-03-15', $investmentData[0]['investment_date']);
    }

    public function test_process_handles_duplicate_investors_in_batch(): void
    {
        // Arrange - Same investor_id appears twice
        $csvContent = "investor_id,name,age,investment_amount,investment_date\n" .
                      "1,John Doe,30,1000.00,01-01-2024\n" .
                      "1,John Doe Updated,31,2000.00,02-01-2024\n";

        $filePath = 'test-dataset.csv';
        $this->createVirtualCsvFile('app/private/' . $filePath, $csvContent);

        $mockDB = Mockery::mock('alias:' . DB::class);
        $mockDB->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                $callback();
            });

        $investorData = [];
        $investorRepo = Mockery::mock(InvestorRepository::class);
        $investorRepo->shouldReceive('upsert')
            ->once()
            ->with(Mockery::on(function ($data) use (&$investorData) {
                $investorData = $data;
                return true;
            }));

        $investmentData = [];
        $investmentRepo = Mockery::mock(InvestmentRepository::class);
        $investmentRepo->shouldReceive('insert')
            ->once()
            ->with(Mockery::on(function ($data) use (&$investmentData) {
                $investmentData = $data;
                return true;
            }));

        $this->mockAppFunction($investorRepo, $investmentRepo);
        $this->mockLogging();
        $this->mockStoragePath();

        // Act
        $this->service->process($filePath);

        // Assert - Only one investor entry (last one wins)
        $this->assertCount(1, $investorData);
        $this->assertEquals('John Doe Updated', $investorData[1]['name']);
        $this->assertEquals(31, $investorData[1]['age']);

        // But two investments
        $this->assertCount(2, $investmentData);
    }

    public function test_process_trims_whitespace_from_csv_data(): void
    {
        // Arrange
        $csvContent = "investor_id,name,age,investment_amount,investment_date\n" .
                      "  1  ,  John Doe  ,  30  ,  1000.00  ,  01-01-2024  \n";

        $filePath = 'test-dataset.csv';
        $this->createVirtualCsvFile('app/private/' . $filePath, $csvContent);

        $mockDB = Mockery::mock('alias:' . DB::class);
        $mockDB->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                $callback();
            });

        $investorData = [];
        $investorRepo = Mockery::mock(InvestorRepository::class);
        $investorRepo->shouldReceive('upsert')
            ->once()
            ->with(Mockery::on(function ($data) use (&$investorData) {
                $investorData = $data;
                return true;
            }));

        $investmentRepo = Mockery::mock(InvestmentRepository::class);
        $investmentRepo->shouldReceive('insert')->once();

        $this->mockAppFunction($investorRepo, $investmentRepo);
        $this->mockLogging();
        $this->mockStoragePath();

        // Act
        $this->service->process($filePath);

        // Assert
        $this->assertEquals('John Doe', $investorData[1]['name']);
        $this->assertEquals(1, $investorData[1]['investor_id']);
        $this->assertEquals(30, $investorData[1]['age']);
    }

    public function test_process_sets_timestamps_on_records(): void
    {
        // Arrange
        $csvContent = "investor_id,name,age,investment_amount,investment_date\n" .
                      "1,John Doe,30,1000.00,01-01-2024\n";

        $filePath = 'test-dataset.csv';
        $this->createVirtualCsvFile('app/private/' . $filePath, $csvContent);

        $mockDB = Mockery::mock('alias:' . DB::class);
        $mockDB->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                $callback();
            });

        $investorData = [];
        $investorRepo = Mockery::mock(InvestorRepository::class);
        $investorRepo->shouldReceive('upsert')
            ->once()
            ->with(Mockery::on(function ($data) use (&$investorData) {
                $investorData = $data;
                return true;
            }));

        $investmentData = [];
        $investmentRepo = Mockery::mock(InvestmentRepository::class);
        $investmentRepo->shouldReceive('insert')
            ->once()
            ->with(Mockery::on(function ($data) use (&$investmentData) {
                $investmentData = $data;
                return true;
            }));

        $this->mockAppFunction($investorRepo, $investmentRepo);
        $this->mockLogging();
        $this->mockStoragePath();

        // Act
        $this->service->process($filePath);

        // Assert
        $this->assertArrayHasKey('created_at', $investorData[1]);
        $this->assertArrayHasKey('updated_at', $investorData[1]);
        $this->assertArrayHasKey('created_at', $investmentData[0]);
        $this->assertArrayHasKey('updated_at', $investmentData[0]);
    }

    /**
     * Helper method to create a CSV file in temp directory
     */
    private function createVirtualCsvFile(string $path, string $content): void
    {
        $fullPath = $this->tempDir . '/' . $path;
        file_put_contents($fullPath, $content);
    }

    /**
     * Helper method to mock database transactions
     */
    private function mockDatabaseTransaction(): void
    {
        $mockDB = Mockery::mock('alias:' . DB::class);
        $mockDB->shouldReceive('transaction')
            ->andReturnUsing(function ($callback) {
                return $callback();
            });
    }

    /**
     * Helper method to mock repositories
     */
    private function mockRepositories(): void
    {
        $investorRepo = Mockery::mock(InvestorRepository::class);
        $investorRepo->shouldReceive('upsert')->andReturn(true);

        $investmentRepo = Mockery::mock(InvestmentRepository::class);
        $investmentRepo->shouldReceive('insert')->andReturn(true);

        $this->mockAppFunction($investorRepo, $investmentRepo);
    }

    /**
     * Helper method to mock logging
     */
    private function mockLogging(): void
    {
        $mockLog = Mockery::mock('alias:' . Log::class);
        $mockLog->shouldReceive('info')->andReturn(null);
        $mockLog->shouldReceive('warning')->andReturn(null);
        $mockLog->shouldReceive('error')->andReturn(null);
    }

    /**
     * Helper method to mock storage_path by overriding config
     */
    private function mockStoragePath(): void
    {
        config(['app.storage_path' => $this->tempDir]);
        $this->app->useStoragePath($this->tempDir);
    }

    /**
     * Helper method to mock app() function for repository resolution
     */
    private function mockAppFunction($investorRepo, $investmentRepo): void
    {
        // Bind mocked repositories to the service container
        $this->app->instance(InvestorRepository::class, $investorRepo);
        $this->app->instance(InvestmentRepository::class, $investmentRepo);
    }
}