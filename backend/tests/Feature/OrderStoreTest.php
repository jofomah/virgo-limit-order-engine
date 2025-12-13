<?php
namespace Tests\Feature\Api;

use App\Enums\Currency;
use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * BUY ORDER TEST
     */
    public function test_user_can_create_buy_order()
    {
        $btc = AssetType::factory()->btc()->create();

        // User has $10,000 available (in cents)
        $user = User::factory()->create([
            'balance_available_units' => 1_000_000, // $10,000
            'balance_locked_units'    => 0,
        ]);

        $payload = [
            'symbol' => 'BTC',
            'side'   => 'buy',
            'price'  => '50000', // $50k
            'amount' => '0.1',   // buy 0.1 BTC
        ];

        $idempotencyKey = Str::uuid()->toString();

        $response = $this->actingAs($user)->postJson('/api/orders', $payload, [
            'Idempotency-Key' => $idempotencyKey,
        ]);

        $response->assertCreated();

        $order = Order::first();

        // Check order fields
        $this->assertEquals($user->id, $order->user_id);
        $this->assertEquals('BTC', $order->symbol);
        $this->assertEquals(OrderSide::BUY, $order->side);
        $this->assertEquals(OrderStatus::OPEN, $order->status);
        $this->assertEquals(Currency::USD->value, $order->currency);

        // Price normalization: 50000 * 10^8
        $this->assertEquals(
            bcmul('50000', bcpow('10', '8')),
            $order->price_numerator
        );

        // Amount conversion: 0.1 * 10^8 = 10,000,000
        $this->assertEquals(10000000, $order->amount_atomic_units);

        $assetScale = $btc->atomic_scale;

        $expectedCost = (int) bcdiv(
            bcmul($order->price_numerator, $order->amount_atomic_units),
            bcpow('10', $order->price_scale + $assetScale),
            0
        );
        // Check user funds
        $user->refresh();
        $this->assertEquals(1_000_000 - $expectedCost, $user->balance_available_units);
        $this->assertEquals($expectedCost, $user->balance_locked_units);
    }

    /**
     * SELL ORDER TEST
     */
    public function test_user_can_create_sell_order()
    {
        $btc = AssetType::factory()->btc()->create();

        $user = User::factory()->create();

        // User owns 1 BTC
        $asset = Asset::factory()->create([
            'user_id'                       => $user->id,
            'symbol'                        => 'BTC',
            'amount_available_atomic_units' => 100_000_000, // 1 BTC
            'amount_locked_atomic_units'    => 0,
        ]);

        $payload = [
            'symbol' => 'BTC',
            'side'   => 'sell',
            'price'  => '50000',
            'amount' => '0.3',
        ];

        $response = $this->actingAs($user)->postJson('/api/orders', $payload);
        $response->assertCreated();

        $order = Order::first();

        $this->assertEquals(OrderSide::SELL, $order->side);
        $this->assertEquals(30_000_000, $order->amount_atomic_units); // 0.3 BTC
        $this->assertEquals(30_000_000, $order->amount_locked_units); // correctly locked

        $asset->refresh();
        $this->assertEquals(70_000_000, $asset->amount_available_atomic_units);
        $this->assertEquals(30_000_000, $asset->amount_locked_atomic_units);
    }

    /**
     * INSUFFICIENT USD FOR BUY ORDER
     */
    public function test_buy_order_fails_if_insufficient_usd()
    {
        $btc = AssetType::factory()->btc()->create();

        $user = User::factory()->create([
            'balance_available_units' => 2000, // $20
        ]);

        $payload = [
            'symbol' => 'BTC',
            'side'   => 'buy',
            'price'  => '50000',
            'amount' => '0.1',
        ];

        $response = $this->actingAs($user)->postJson('/api/orders', $payload);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Insufficient available USD balance.']);
    }

    /**
     * INSUFFICIENT ASSET FOR SELL ORDER
     */
    public function test_sell_order_fails_if_insufficient_asset()
    {
        $btc = AssetType::factory()->btc()->create();

        $user = User::factory()->create();

        Asset::factory()->create([
            'user_id'                       => $user->id,
            'symbol'                        => 'BTC',
            'amount_available_atomic_units' => 5_000_000, // 0.05 BTC
        ]);

        $payload = [
            'symbol' => 'BTC',
            'side'   => 'sell',
            'price'  => '50000',
            'amount' => '1',
        ];

        $response = $this->actingAs($user)->postJson('/api/orders', $payload);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Insufficient available asset balance.']);
    }

    /**
     * VALIDATION TEST
     */
    public function test_validation_fails_for_invalid_payload()
    {
        $response = $this->actingAs(User::factory()->create())
            ->postJson('/api/orders', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['symbol', 'side', 'price', 'amount']);
    }
}
