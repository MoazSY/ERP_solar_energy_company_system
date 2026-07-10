<?php

namespace Tests\Unit;

use App\Services\ApiSyriaService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiSyriaServiceTest extends TestCase
{
    public function test_it_converts_usd_amount_using_latest_lirascope_rate(): void
    {
        Http::fake([
            'https://lirascope.syria-cloud.sy/api/v1/rates/latest*' => Http::response([
                'effectiveRates' => [
                    ['currency' => 'USD', 'mid' => 15000.5],
                ],
            ], 200),
        ]);

        $service = new ApiSyriaService();

        $this->assertSame(1500050.0, $service->convertAmountToSyp(100.0, 'USD'));
    }
}
