<?php

namespace Tests\Feature;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCancelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * USER CAN CANCEL BUY ORDER (USD unlock)
     */
    public function test_user_can_cancel_buy_order()
    {
        AssetType::factory()->btc()->create(); // ensure BTC exists

        $user = User::factory()->create([
            'balance_available_units' => 1_000_000,
            'balance_locked_units'    => 0,
        ]);

        // BUY order: lock $5000
        $order = Order::factory()->create([
            'user_id'             => $user->id,
            'symbol'              => 'BTC',
            'side'                => OrderSide::BUY,
            'price_numerator'     => 50000 * (10 ** 8),
            'price_scale'         => 8,
            'amount_atomic_units' => 10_000_000, // 0.1 BTC
            'amount_locked_units' => 5000,       // locked USD
            'currency'            => 'USD',
            'status'              => OrderStatus::OPEN,
        ]);

        // simulate locked USD
        $user->update([
            'balance_available_units' => 995_000,
            'balance_locked_units'    => 5000,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/orders/{$order->id}/cancel");

        $response->assertOk()
                 ->assertJson(['status' => 'cancelled']);

        $user->refresh();
        $order->refresh();

        // Check status
        $this->assertEquals(OrderStatus::CANCELLED, $order->status);

        // Check balance refund
        $this->assertEquals(1_000_000, $user->balance_available_units);
        $this->assertEquals(0, $user->balance_locked_units);
    }

    /**
     * USER CAN CANCEL SELL ORDER (asset unlock)
     */
    public function test_user_can_cancel_sell_order()
    {
        AssetType::factory()->btc()->create(); // ensure BTC exists

        $user = User::factory()->create();

        // user has 1 BTC
        $asset = Asset::factory()
            ->forUser($user)
            ->symbol('BTC')
            ->available(100_000_000) // 1 BTC
            ->locked(0)
            ->create();

        // LOCK 0.3 BTC for sell order
        $order = Order::factory()->create([
            'user_id'             => $user->id,
            'symbol'              => 'BTC',
            'side'                => OrderSide::SELL,
            'price_numerator'     => 50000 * (10 ** 8),
            'price_scale'         => 8,
            'amount_atomic_units' => 30_000_000, // 0.3 BTC
            'amount_locked_units' => 30_000_000,
            'currency'            => 'USD',
            'status'              => OrderStatus::OPEN,
        ]);

        // reflect locked asset
        $asset->update([
            'amount_available_atomic_units' => 70_000_000,
            'amount_locked_atomic_units'    => 30_000_000,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/orders/{$order->id}/cancel");

        $response->assertOk()
                 ->assertJson(['status' => 'cancelled']);

        $order->refresh();
        $asset->refresh();

        $this->assertEquals(OrderStatus::CANCELLED, $order->status);

        // asset restored
        $this->assertEquals(100_000_000, $asset->amount_available_atomic_units);
        $this->assertEquals(0, $asset->amount_locked_atomic_units);
    }

    /**
     * VALIDATION — must be an open order
     */
    public function test_cancel_fails_if_order_not_open()
    {
        AssetType::factory()->btc()->create();

        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'symbol'  => 'BTC',
            'side'    => OrderSide::BUY,
            'status'  => OrderStatus::FILLED, // cannot cancel
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(422); // authorization blocked or policy fail
    }

    /**
     * AUTHORIZATION — user cannot cancel someone else's order
     */
    public function test_user_cannot_cancel_other_users_order()
    {
        AssetType::factory()->btc()->create();

        $owner = User::factory()->create();
        $other = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $owner->id,
            'symbol'  => 'BTC',
            'side'    => OrderSide::BUY,
            'status'  => OrderStatus::OPEN,
        ]);

        $response = $this->actingAs($other)
            ->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(403);
    }

    /**
     * VALIDATION — order ID must exist and be OPEN
     */
    public function test_cancel_fails_for_invalid_id()
    {
        AssetType::factory()->btc()->create();

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/orders/999/cancel");

        // CancelOrderRequest: "id must exist and be OPEN"
        $response->assertStatus(404);
    }
}
