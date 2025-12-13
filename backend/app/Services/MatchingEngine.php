<?php

namespace App\Domain\Matching;

use App\Models\Order;
use App\Models\Asset;
use App\Models\AssetType;
use App\Services\CommissionService;
use Illuminate\Support\Facades\DB;

class MatchingEngine
{
    public function match(Order $order): ?array
    {
        $symbol = $order->symbol;

        $counter = $this->findCounterOrder($order);
        if (! $counter) {
            return null;
        }

        if ($counter->amount_atomic_units !== $order->amount_atomic_units) {
            return null;
        }

        return DB::transaction(function () use ($order, $counter, $symbol) {

            $buy  = $order->side->isBuy() ? $order : $counter;
            $sell = $order->side->isSell() ? $order : $counter;

            // Lock rows
            $buyer  = $buy->user()->lockForUpdate()->first();
            $seller = $sell->user()->lockForUpdate()->first();

            $sellerAsset = Asset::where('user_id', $seller->id)->where('symbol', $symbol)->lockForUpdate()->first();
            $buyerAsset  = Asset::where('user_id', $buyer->id )->where('symbol', $symbol)->lockForUpdate()->first();

            $assetType = AssetType::findOrFail($symbol);

            $usdCents = $this->computeUsdCents(
                $buy->price_numerator,
                $buy->price_scale,
                $buy->amount_atomic_units,
                $assetType->atomic_scale,
            );

            if ($buyer->balance_locked_units < $usdCents) {
                $buy->update(['status' => Order::STATUS_CANCELLED]);
                return null;
            }

            if ($sellerAsset->amount_locked_atomic_units < $sell->amount_atomic_units) {
                $sell->update(['status' => Order::STATUS_CANCELLED]);
                return null;
            }

            $feeCents = CommissionService::computeFee($usdCents);

            // -------------------------
            // APPLY STATE CHANGES
            // -------------------------

            // Buyer locked USD decreases (net trade + fee)
            $locked = $buy->amount_locked_units;
            $used   = $usdCents + $feeCents;
            $refund = max(0, $locked - $used);

            $buyer->balance_locked_units    -= $locked;
            $buyer->balance_available_units += $refund;
            $buyer->save();

            // Buyer's asset +amount
            if (! $buyerAsset) {
                $buyerAsset = Asset::create([
                    'user_id' => $buyer->id,
                    'symbol'  => $symbol,
                    'amount_available_atomic_units' => 0,
                    'amount_locked_atomic_units'    => 0,
                ]);
            }

            $buyerAsset->amount_available_atomic_units += $buy->amount_atomic_units;
            $buyerAsset->save();

            // Seller asset locked decreases
            $sellerAsset->amount_locked_atomic_units -= $sell->amount_atomic_units;
            $sellerAsset->save();

            // Seller USD credited net of fee
            $seller->balance_available_units += ($usdCents - $feeCents);
            $seller->save();

            // -------------------------
            // UPDATE ORDER STATUS
            // -------------------------
            $buy->update(['status' => Order::STATUS_FILLED]);
            $sell->update(['status' => Order::STATUS_FILLED]);

            return [
                'buy_order'  => $buy,
                'sell_order' => $sell,
                'usd_volume' => $usdCents,
                'fee'        => $feeCents,
            ];
        });
    }

    private function findCounterOrder(Order $order): ?Order
    {
        $side = $order->side;
        $symbol = $order->symbol;

        if ($side->isBuy()) {
            return Order::where('symbol', $symbol)
                ->where('side', 'sell')
                ->where('status', Order::STATUS_OPEN)
                ->where('price_numerator', '<=', $order->price_numerator)
                ->orderBy('id')
                ->lockForUpdate()
                ->first();
        }

        return Order::where('symbol', $symbol)
            ->where('side', 'buy')
            ->where('status', Order::STATUS_OPEN)
            ->where('price_numerator', '>=', $order->price_numerator)
            ->orderBy('id')
            ->lockForUpdate()
            ->first();
    }

    private function computeUsdCents(int $priceNum, int $priceScale, int $amountAtomic, int $atomicScale): int
    {
        return (int) bcdiv(
            bcmul($priceNum, $amountAtomic),
            bcpow('10', $priceScale + $atomicScale),
            0
        );
    }
}
