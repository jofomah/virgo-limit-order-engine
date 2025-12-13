<?php
namespace App\Services;

class CommissionService
{
    /**
     * Fee in basis points (e.g., 150 = 1.5%)
     */
    public const PLATFORM_COMMISSION_IN_BPS = 150;

    /**
     * Compute fee from a traded volume expressed in atomic units.
     *
     * @param int $volumeUnits  Total trade volume in atomic (smallest indivisible) units
     *
     * @return int Fee amount in the SAME atomic units as the volume
     */
    public static function computeFee(int $volumeUnits): int
    {
        if ($volumeUnits < 0) {
            throw new \ValueError('Volume cannot be negative');
        }
        // Formula: (volume × bps) / 10,000
        return intdiv($volumeUnits * self::PLATFORM_COMMISSION_IN_BPS, 10_000);
    }
}
