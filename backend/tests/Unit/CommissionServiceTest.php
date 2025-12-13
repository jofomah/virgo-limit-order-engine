<?php

namespace Tests\Unit;

use App\Services\CommissionService;
use PHPUnit\Framework\TestCase;

class CommissionServiceTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_compute_fee(): void
    {
        // Example:
        // 0.01 BTC @ 95,000 USD = 950 USD
        // Fee = 950 * 1.5% = 14.25 USD = 1425 units

        // Volume in atomic units (USD cents)
        $volumeUnits = 950 * 100; // = 95,000 units

        // Expected fee in units
        $expectedFeeUnits = 1425; // 14.25 USD

        // Compute fee using your service
        $fee = CommissionService::computeFee($volumeUnits);

        $this->assertEquals(
            $expectedFeeUnits,
            $fee,
            "Commission fee should equal 1.5% of the traded volume."
        );
    }

    public function test_computes_standard_commission_correctly()
    {
        // Example: 0.01 BTC @ 95,000 = 950 USD → 95,000 units → fee = 1.5% = 1425 units
        $volumeUnits = 950 * 100; // 95,000
        $fee = CommissionService::computeFee($volumeUnits);

        $this->assertEquals(1425, $fee);
    }

    public function test_returns_zero_fee_for_zero_volume()
    {
        $fee = CommissionService::computeFee(0, 150);
        $this->assertEquals(0, $fee);
    }

    public function test_calculates_fee_for_one_unit_volume()
    {
        // 1 unit @ 150 bps → fee = (1 * 150) / 10000 = 0
        $fee = CommissionService::computeFee(1,);
        $this->assertEquals(0, $fee);
    }
}
