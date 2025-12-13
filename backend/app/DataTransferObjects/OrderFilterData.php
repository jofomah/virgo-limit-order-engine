<?php
namespace App\DataTransferObjects;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Http\Requests\Order\OrderFilterRequest;

class OrderFilterData
{
    public function __construct(
        public readonly ?string $symbol = null,
        public readonly ?OrderSide $side = null,
        public readonly ?OrderStatus $status = OrderStatus::OPEN->value
    ) {
    }

    public static function fromOrderFilterRequest(OrderFilterRequest $request): OrderFilterData
    {
        return new self(
            $request->query('symbol'),
            OrderSide::tryFrom($request->query('side')),
            OrderStatus::tryFrom($request->query('status'))
        );
    }
}
