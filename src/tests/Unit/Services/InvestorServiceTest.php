<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Repositories\InvestorRepository;
use App\Services\InvestorService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class InvestorServiceTest extends TestCase
{
    private InvestorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InvestorService();
    }

    public function test_get_all_investors_with_total_investment_amount_returns_collection(): void
    {
        // Arrange
        $mockBuilder = Mockery::mock(Builder::class);
        $mockBuilder->shouldReceive('get')
            ->once()
            ->andReturn(collect([]));

        $mockRepo = Mockery::mock(InvestorRepository::class);
        $mockRepo->shouldReceive('allWithTotalInvestmentAmount')
            ->once()
            ->andReturn($mockBuilder);

        $this->app->instance(InvestorRepository::class, $mockRepo);

        // Act
        $result = $this->service->getAllInvestorsWithTotalInvestmentAmount();

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEmpty($result);
    }

    public function test_get_all_investors_rounds_total_investment_amount_to_two_decimals(): void
    {
        // Arrange
        $investor1 = (object) [
            'id' => 1,
            'name' => 'John Doe',
            'total_investment_amount' => 1000.555,
        ];

        $investor2 = (object) [
            'id' => 2,
            'name' => 'Jane Smith',
            'total_investment_amount' => 2000.444,
        ];

        $mockBuilder = Mockery::mock(Builder::class);
        $mockBuilder->shouldReceive('get')
            ->once()
            ->andReturn(collect([$investor1, $investor2]));

        $mockRepo = Mockery::mock(InvestorRepository::class);
        $mockRepo->shouldReceive('allWithTotalInvestmentAmount')
            ->once()
            ->andReturn($mockBuilder);

        $this->app->instance(InvestorRepository::class, $mockRepo);

        // Act
        $result = $this->service->getAllInvestorsWithTotalInvestmentAmount();

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals(1000.56, $result[0]->total_investment_amount);
        $this->assertEquals(2000.44, $result[1]->total_investment_amount);
    }

    public function test_get_all_investors_handles_exact_two_decimal_amounts(): void
    {
        // Arrange
        $investor = (object) [
            'id' => 1,
            'name' => 'John Doe',
            'total_investment_amount' => 1000.50,
        ];

        $mockBuilder = Mockery::mock(Builder::class);
        $mockBuilder->shouldReceive('get')
            ->once()
            ->andReturn(collect([$investor]));

        $mockRepo = Mockery::mock(InvestorRepository::class);
        $mockRepo->shouldReceive('allWithTotalInvestmentAmount')
            ->once()
            ->andReturn($mockBuilder);

        $this->app->instance(InvestorRepository::class, $mockRepo);

        // Act
        $result = $this->service->getAllInvestorsWithTotalInvestmentAmount();

        // Assert
        $this->assertEquals(1000.50, $result[0]->total_investment_amount);
    }

    public function test_get_all_investors_handles_zero_amount(): void
    {
        // Arrange
        $investor = (object) [
            'id' => 1,
            'name' => 'John Doe',
            'total_investment_amount' => 0.0,
        ];

        $mockBuilder = Mockery::mock(Builder::class);
        $mockBuilder->shouldReceive('get')
            ->once()
            ->andReturn(collect([$investor]));

        $mockRepo = Mockery::mock(InvestorRepository::class);
        $mockRepo->shouldReceive('allWithTotalInvestmentAmount')
            ->once()
            ->andReturn($mockBuilder);

        $this->app->instance(InvestorRepository::class, $mockRepo);

        // Act
        $result = $this->service->getAllInvestorsWithTotalInvestmentAmount();

        // Assert
        $this->assertEquals(0.0, $result[0]->total_investment_amount);
    }

    public function test_get_all_investors_handles_large_amounts(): void
    {
        // Arrange
        $investor = (object) [
            'id' => 1,
            'name' => 'John Doe',
            'total_investment_amount' => 999999.999,
        ];

        $mockBuilder = Mockery::mock(Builder::class);
        $mockBuilder->shouldReceive('get')
            ->once()
            ->andReturn(collect([$investor]));

        $mockRepo = Mockery::mock(InvestorRepository::class);
        $mockRepo->shouldReceive('allWithTotalInvestmentAmount')
            ->once()
            ->andReturn($mockBuilder);

        $this->app->instance(InvestorRepository::class, $mockRepo);

        // Act
        $result = $this->service->getAllInvestorsWithTotalInvestmentAmount();

        // Assert
        $this->assertEquals(1000000.0, $result[0]->total_investment_amount);
    }

    public function test_get_all_investors_rounds_up_correctly(): void
    {
        // Arrange
        $investor1 = (object) [
            'id' => 1,
            'name' => 'John Doe',
            'total_investment_amount' => 100.125,
        ];

        $investor2 = (object) [
            'id' => 2,
            'name' => 'Jane Smith',
            'total_investment_amount' => 100.115,
        ];

        $mockBuilder = Mockery::mock(Builder::class);
        $mockBuilder->shouldReceive('get')
            ->once()
            ->andReturn(collect([$investor1, $investor2]));

        $mockRepo = Mockery::mock(InvestorRepository::class);
        $mockRepo->shouldReceive('allWithTotalInvestmentAmount')
            ->once()
            ->andReturn($mockBuilder);

        $this->app->instance(InvestorRepository::class, $mockRepo);

        // Act
        $result = $this->service->getAllInvestorsWithTotalInvestmentAmount();

        // Assert
        $this->assertEquals(100.13, $result[0]->total_investment_amount); // Rounds up from .125
        $this->assertEquals(100.12, $result[1]->total_investment_amount); // Rounds up from .115
    }

    public function test_get_all_investors_rounds_down_correctly(): void
    {
        // Arrange
        $investor1 = (object) [
            'id' => 1,
            'name' => 'John Doe',
            'total_investment_amount' => 100.124,
        ];

        $investor2 = (object) [
            'id' => 2,
            'name' => 'Jane Smith',
            'total_investment_amount' => 100.114,
        ];

        $mockBuilder = Mockery::mock(Builder::class);
        $mockBuilder->shouldReceive('get')
            ->once()
            ->andReturn(collect([$investor1, $investor2]));

        $mockRepo = Mockery::mock(InvestorRepository::class);
        $mockRepo->shouldReceive('allWithTotalInvestmentAmount')
            ->once()
            ->andReturn($mockBuilder);

        $this->app->instance(InvestorRepository::class, $mockRepo);

        // Act
        $result = $this->service->getAllInvestorsWithTotalInvestmentAmount();

        // Assert
        $this->assertEquals(100.12, $result[0]->total_investment_amount); // Rounds down from .124
        $this->assertEquals(100.11, $result[1]->total_investment_amount); // Rounds down from .114
    }

    public function test_get_all_investors_preserves_other_investor_properties(): void
    {
        // Arrange
        $investor = (object) [
            'id' => 1,
            'investor_id' => 100,
            'name' => 'John Doe',
            'age' => 30,
            'email' => 'john@example.com',
            'total_investment_amount' => 1000.555,
            'created_at' => '2024-01-01',
        ];

        $mockBuilder = Mockery::mock(Builder::class);
        $mockBuilder->shouldReceive('get')
            ->once()
            ->andReturn(collect([$investor]));

        $mockRepo = Mockery::mock(InvestorRepository::class);
        $mockRepo->shouldReceive('allWithTotalInvestmentAmount')
            ->once()
            ->andReturn($mockBuilder);

        $this->app->instance(InvestorRepository::class, $mockRepo);

        // Act
        $result = $this->service->getAllInvestorsWithTotalInvestmentAmount();

        // Assert
        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals(100, $result[0]->investor_id);
        $this->assertEquals('John Doe', $result[0]->name);
        $this->assertEquals(30, $result[0]->age);
        $this->assertEquals('john@example.com', $result[0]->email);
        $this->assertEquals('2024-01-01', $result[0]->created_at);
        $this->assertEquals(1000.56, $result[0]->total_investment_amount);
    }

    public function test_get_all_investors_handles_multiple_investors(): void
    {
        // Arrange
        $investors = collect([
            (object) ['id' => 1, 'name' => 'Investor 1', 'total_investment_amount' => 100.111],
            (object) ['id' => 2, 'name' => 'Investor 2', 'total_investment_amount' => 200.222],
            (object) ['id' => 3, 'name' => 'Investor 3', 'total_investment_amount' => 300.333],
            (object) ['id' => 4, 'name' => 'Investor 4', 'total_investment_amount' => 400.444],
            (object) ['id' => 5, 'name' => 'Investor 5', 'total_investment_amount' => 500.555],
        ]);

        $mockBuilder = Mockery::mock(Builder::class);
        $mockBuilder->shouldReceive('get')
            ->once()
            ->andReturn($investors);

        $mockRepo = Mockery::mock(InvestorRepository::class);
        $mockRepo->shouldReceive('allWithTotalInvestmentAmount')
            ->once()
            ->andReturn($mockBuilder);

        $this->app->instance(InvestorRepository::class, $mockRepo);

        // Act
        $result = $this->service->getAllInvestorsWithTotalInvestmentAmount();

        // Assert
        $this->assertCount(5, $result);
        $this->assertEquals(100.11, $result[0]->total_investment_amount);
        $this->assertEquals(200.22, $result[1]->total_investment_amount);
        $this->assertEquals(300.33, $result[2]->total_investment_amount);
        $this->assertEquals(400.44, $result[3]->total_investment_amount);
        $this->assertEquals(500.56, $result[4]->total_investment_amount);
    }

    public function test_get_all_investors_calls_repository_method_once(): void
    {
        // Arrange
        $mockBuilder = Mockery::mock(Builder::class);
        $mockBuilder->shouldReceive('get')
            ->once()
            ->andReturn(collect([]));

        $mockRepo = Mockery::mock(InvestorRepository::class);
        $mockRepo->shouldReceive('allWithTotalInvestmentAmount')
            ->once()
            ->andReturn($mockBuilder);

        $this->app->instance(InvestorRepository::class, $mockRepo);

        // Act
        $this->service->getAllInvestorsWithTotalInvestmentAmount();

        // Assert
        $mockRepo->shouldHaveReceived('allWithTotalInvestmentAmount')->once();
        $mockBuilder->shouldHaveReceived('get')->once();
    }

    public function test_get_all_investors_handles_negative_amounts(): void
    {
        // Arrange
        $investor = (object) [
            'id' => 1,
            'name' => 'John Doe',
            'total_investment_amount' => -100.555,
        ];

        $mockBuilder = Mockery::mock(Builder::class);
        $mockBuilder->shouldReceive('get')
            ->once()
            ->andReturn(collect([$investor]));

        $mockRepo = Mockery::mock(InvestorRepository::class);
        $mockRepo->shouldReceive('allWithTotalInvestmentAmount')
            ->once()
            ->andReturn($mockBuilder);

        $this->app->instance(InvestorRepository::class, $mockRepo);

        // Act
        $result = $this->service->getAllInvestorsWithTotalInvestmentAmount();

        // Assert
        $this->assertEquals(-100.56, $result[0]->total_investment_amount);
    }

    public function test_get_all_investors_handles_whole_numbers(): void
    {
        // Arrange
        $investor = (object) [
            'id' => 1,
            'name' => 'John Doe',
            'total_investment_amount' => 1000,
        ];

        $mockBuilder = Mockery::mock(Builder::class);
        $mockBuilder->shouldReceive('get')
            ->once()
            ->andReturn(collect([$investor]));

        $mockRepo = Mockery::mock(InvestorRepository::class);
        $mockRepo->shouldReceive('allWithTotalInvestmentAmount')
            ->once()
            ->andReturn($mockBuilder);

        $this->app->instance(InvestorRepository::class, $mockRepo);

        // Act
        $result = $this->service->getAllInvestorsWithTotalInvestmentAmount();

        // Assert
        $this->assertEquals(1000.0, $result[0]->total_investment_amount);
    }

    public function test_get_all_investors_handles_very_small_amounts(): void
    {
        // Arrange
        $investor = (object) [
            'id' => 1,
            'name' => 'John Doe',
            'total_investment_amount' => 0.001,
        ];

        $mockBuilder = Mockery::mock(Builder::class);
        $mockBuilder->shouldReceive('get')
            ->once()
            ->andReturn(collect([$investor]));

        $mockRepo = Mockery::mock(InvestorRepository::class);
        $mockRepo->shouldReceive('allWithTotalInvestmentAmount')
            ->once()
            ->andReturn($mockBuilder);

        $this->app->instance(InvestorRepository::class, $mockRepo);

        // Act
        $result = $this->service->getAllInvestorsWithTotalInvestmentAmount();

        // Assert
        $this->assertEquals(0.0, $result[0]->total_investment_amount);
    }
}