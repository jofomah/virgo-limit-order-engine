<?php
namespace Tests\Feature\Api;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Models\AssetType;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_orders_index()
    {
        $this->getJson('/api/orders')->assertUnauthorized();
    }

    public function test_it_returns_paginated_orders_with_price_and_amount()
    {
        $btc = AssetType::factory()->create([
            'symbol'       => 'BTC',
            'atomic_scale' => 8,
        ]);

        $user = User::factory()->create();

        $priceNumerator = 950000000; // 95000 * 10^4
        $priceScale     = 4;

        $orders = Order::factory()
            ->count(3)
            ->for($user)
            ->forAssetType($btc)
            ->state([
                'symbol'              => 'BTC',
                'price_numerator'     => $priceNumerator,
                'price_scale'         => $priceScale,
                'amount_atomic_units' => 150000000, // 1.5 BTC
                'amount_locked_units' => 10,
                'side'                => OrderSide::BUY,
                'status'              => OrderStatus::OPEN,
            ])
            ->create();

        $response = $this->actingAs($user)->getJson('/api/orders');

        $response->assertOk()
            ->assertJsonStructure([
                'data'  => [[
                    'id',
                    'symbol',
                    'side',
                    'price',
                    'amount',
                    'created_at',
                    'updated_at',
                ]],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta'  => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $first = $response->json('data.0');

        $this->assertEquals('BTC', $first['symbol']);
        $this->assertEquals('95000.00000000', $first['price']);
        $this->assertEquals('1.50000000', $first['amount']);
    }

    public function test_it_filters_by_symbol()
    {
        $btc = AssetType::factory()->create(['symbol' => 'BTC', 'atomic_scale' => 8]);
        $eth = AssetType::factory()->create(['symbol' => 'ETH', 'atomic_scale' => 18]);

        $user = User::factory()->create();

        Order::factory()->for($user)->forAssetType($btc)->state([
            'symbol'              => 'BTC',
            'amount_locked_units' => 0,
            'status'              => OrderStatus::OPEN,
        ])->create();

        Order::factory()->for($user)->forAssetType($eth)->state([
            'symbol'              => 'ETH',
            'amount_locked_units' => 0,
            'status'              => OrderStatus::OPEN,
        ])->create();

        $response = $this->actingAs($user)->getJson('/api/orders?symbol=BTC');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals('BTC', $data[0]['symbol']);
    }

    public function test_it_filters_by_side_and_status()
    {
        $asset = AssetType::factory()->create(['symbol' => 'BTC', 'atomic_scale' => 8]);
        $user  = User::factory()->create();

        Order::factory()->for($user)->forAssetType($asset)->state([
            'symbol'              => 'BTC',
            'side'                => OrderSide::BUY,
            'status'              => OrderStatus::OPEN,
            'amount_locked_units' => 0,
        ])->create();

        Order::factory()->for($user)->forAssetType($asset)->state([
            'symbol'              => 'BTC',
            'side'                => OrderSide::SELL,
            'status'              => OrderStatus::CANCELLED,
            'amount_locked_units' => 0,
        ])->create();

        $response = $this->actingAs($user)
            ->getJson('/api/orders?side=buy&status=' . OrderStatus::OPEN->value);

        $response->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    public function test_validation_fails_for_unknown_symbol()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/orders?symbol=XXXX')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['symbol']);
    }
}
