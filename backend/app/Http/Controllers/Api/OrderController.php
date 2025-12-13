<?php
namespace App\Http\Controllers\Api;

use App\DataTransferObjects\OrderFilterData;
use App\Enums\OrderSide;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\OrderFilterRequest;
use App\Http\Requests\Order\OrderStoreRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;

class OrderController extends Controller
{
    use AuthorizesRequests ;

    /**
     * GET /api/orders?symbol=BTC
     */
    public function index(OrderFilterRequest $request, OrderService $orderService): JsonResource
    {
        $filterData = OrderFilterData::fromOrderFilterRequest($request);
        $perPage    = $request->query('per_page', 20);

        $orders = $orderService->getPaginatedFilteredOrders($filterData, $perPage);

        return OrderResource::collection($orders);
    }

    public function store(OrderStoreRequest $request, OrderService $orderService): JsonResponse
    {
        $data = $request->normalized();

        if ($data['side'] === OrderSide::BUY->value) {
            $order = $orderService->createBuyOrder(
                userId: $request->user()->id,
                symbol: $data['symbol'],
                priceNumerator: $data['price_numerator'],
                priceScale: $data['price_scale'],
                amountAtomic: $data['amount_atomic_units'],
                idempotencyKey: $data['idempotency_key']
            );

            return response()->json(new OrderResource($order), Response::HTTP_CREATED);
        }

        $order = $orderService->createSellOrder(
            userId: $request->user()->id,
            symbol: $data['symbol'],
            priceNumerator: $data['price_numerator'],
            priceScale: $data['price_scale'],
            amountAtomic: $data['amount_atomic_units'],
            idempotencyKey: $data['idempotency_key']
        );

        return response()->json(new OrderResource($order), Response::HTTP_CREATED);
    }

    public function cancel(Order $order, OrderService $orderService): JsonResponse
    {
        $this->authorize('cancel', $order);

        $orderService->cancelOrder($order);

        return response()->json(['status' => 'cancelled']);
    }
}
