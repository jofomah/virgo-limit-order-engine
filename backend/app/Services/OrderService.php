<?php
namespace App\Services;

use App\DataTransferObjects\OrderFilterData;
use App\Enums\Currency;
use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Order;
use App\Models\User;
use App\Repositories\OrderRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use App\Exceptions\InvalidOrderStatusException;
use App\Jobs\MatchOrderJob;

class OrderService
{
    public function __construct(
        private OrderRepository $orderRepository
    ) {}

    /**
     * Paginated orders with filtering.
     */
    public function getPaginatedFilteredOrders(OrderFilterData $filter, int $perPage): LengthAwarePaginator
    {
        return $this->orderRepository->paginate($filter, $perPage);
    }

    /**
     * BUY ORDER — Lock USD funds
     */
    public function createBuyOrder(
        int $userId,
        string $symbol,
        int $priceNumerator,
        int $priceScale,
        int $amountAtomic,
        ?string $idempotencyKey = null
    ) {
        $order = DB::transaction(function () use (
            $userId,
            $symbol,
            $priceNumerator,
            $priceScale,
            $amountAtomic,
            $idempotencyKey
        ) {
            $user = User::lockForUpdate()->findOrFail($userId);

            // cost = price * amount
            $assetDecimals = AssetType::find($symbol)->atomic_scale;

            $costMinor = (int) bcdiv(
                bcmul($priceNumerator, $amountAtomic),
                bcpow('10', $priceScale + $assetDecimals),
                0
            );

            if ($costMinor <= 0) {
                throw new \RuntimeException("Invalid USD cost calculated.");
            }

            if ($user->balance_available_units < $costMinor) {
                throw new InsufficientBalanceException("Insufficient available USD balance.");
            }

            // Move USD → locked
            $user->balance_available_units -= $costMinor;
            $user->balance_locked_units += $costMinor;
            $user->save();

            // Create Order
            return $this->orderRepository->create([
                'user_id'             => $userId,
                'symbol'              => $symbol,
                'side'                => OrderSide::BUY,
                'price_numerator'     => $priceNumerator,
                'price_scale'         => $priceScale,
                'amount_atomic_units' => $amountAtomic,
                'amount_locked_units' => $costMinor,
                'currency'            => Currency::USD->value,
                'status'              => OrderStatus::OPEN,
                'idempotency_key'     => $idempotencyKey,
            ]);
        });

        MatchOrderJob::dispatch($order->id);

        return $order;
    }

    /**
     * SELL ORDER — Lock asset units
     */
    public function createSellOrder(
        int $userId,
        string $symbol,
        int $priceNumerator,
        int $priceScale,
        int $amountAtomic,
        ?string $idempotencyKey = null
    ) {
        $order = DB::transaction(function () use (
            $userId,
            $symbol,
            $priceNumerator,
            $priceScale,
            $amountAtomic,
            $idempotencyKey
        ) {
            $asset = Asset::where('user_id', $userId)
                ->where('symbol', $symbol)
                ->lockForUpdate()
                ->first();

            if (! $asset || $asset->amount_available_atomic_units < $amountAtomic) {
                throw new InsufficientBalanceException("Insufficient available asset balance.");
            }

            // Lock asset units
            $asset->amount_available_atomic_units -= $amountAtomic;
            $asset->amount_locked_atomic_units += $amountAtomic;
            $asset->save();

            // Create Order
            return $this->orderRepository->create([
                'user_id'             => $userId,
                'symbol'              => $symbol,
                'side'                => OrderSide::SELL,
                'price_numerator'     => $priceNumerator,
                'price_scale'         => $priceScale,
                'amount_atomic_units' => $amountAtomic,
                'amount_locked_units' => $amountAtomic,
                'currency'            => Currency::USD->value,
                'status'              => OrderStatus::OPEN,
                'idempotency_key'     => $idempotencyKey,
            ]);
        });

        MatchOrderJob::dispatch($order->id);

        return $order;
    }

    public function cancelOrder(Order $order): void
    {
        if ($order->status != OrderStatus::OPEN) {
            throw new InvalidOrderStatusException();
        }
        DB::transaction(function () use ($order) {

            $order->status = OrderStatus::CANCELLED;
            $order->save();

            if ($order->side === OrderSide::BUY) {
                // Refund locked USD
                $user = User::lockForUpdate()->find($order->user_id);

                $user->balance_available_units += $order->amount_locked_units;
                $user->balance_locked_units -= $order->amount_locked_units;
                $user->save();

            } else {
                // SELL ORDER — unlock asset
                $asset = Asset::where('user_id', $order->user_id)
                    ->where('symbol', $order->symbol)
                    ->lockForUpdate()
                    ->first();

                if ($asset) {
                    $asset->amount_locked_atomic_units -= $order->amount_locked_units;
                    $asset->amount_available_atomic_units += $order->amount_locked_units;
                    $asset->save();
                }
            }
        });
    }

}
