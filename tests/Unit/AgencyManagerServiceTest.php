<?php

namespace Tests\Unit;

use App\Models\Agency_manager;
use App\Repositories\AgencyManagerRepositoryInterface;
use App\Repositories\TokenRepositoryInterface;
use App\Services\AgencyManagerService;
use App\Services\ApiSyriaService;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AgencyManagerServiceTest extends TestCase
{
    public function test_filter_most_amount_sales_company_calls_repository_with_filters(): void
    {
        $manager = new Agency_manager([
            'id' => 7,
            'first_name' => 'Ali',
            'last_name' => 'Hassan',
            'email' => 'ali@test.com',
            'password' => bcrypt('secret123'),
        ]);

        Auth::shouldReceive('guard')->with('agency_manager')->andReturnSelf();
        Auth::shouldReceive('user')->andReturn($manager);

        $expected = [
            [
                'company' => ['id' => 9, 'company_name' => 'Alpha Solar'],
                'currency' => 'USD',
                'total_sales' => 1500.0,
                'invoice_count' => 2,
            ],
        ];

        $repository = \Mockery::mock(AgencyManagerRepositoryInterface::class);
        $repository
            ->shouldReceive('filter_most_amount_sales_company')
            ->once()
            ->with($manager, [
                'company_id' => 9,
                'date_from' => '2026-01-01',
                'date_to' => '2026-01-31',
                'min_amount' => 500,
            ])
            ->andReturn($expected);

        $service = new AgencyManagerService(
            $repository,
            \Mockery::mock(TokenRepositoryInterface::class),
            \Mockery::mock(ApiSyriaService::class)
        );

        $result = $service->filter_most_amount_sales_company([
            'company_id' => 9,
            'date_from' => '2026-01-01',
            'date_to' => '2026-01-31',
            'min_amount' => 500,
        ]);

        $this->assertSame($expected, $result);
    }
}
