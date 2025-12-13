<?php

namespace App\Jobs;

use App\Models\Order;
use App\Domain\Matching\MatchingEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Queue\ShouldQueue;

class MatchOrderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $orderId) {}

    public function handle(MatchingEngine $engine)
    {
        $order = Order::find($this->orderId);
        if (!$order || !$order->isOpen()) return;

        $lock = Cache::lock("match:{$order->symbol}", 5);
        $lock->block(2000, function () use ($order, $engine) {

            $result = $engine->match($order);
        });
    }
}
