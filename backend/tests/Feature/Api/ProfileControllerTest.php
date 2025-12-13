<?php

namespace Tests\Feature\Api;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_profile_endpoint()
    {
        $this->getJson('/api/profile')
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }

    public function authenticated_user_receives_correct_profile_payload()
    {
        // Create verified user
        $user = User::factory()->create([
            'name'                   => 'Trader 1',
            'email'                  => 'trader1@example.com',
            'email_verified_at'      => now(),
            'currency'               => 'USD',
            'balance_available_units'=> 12345,  // => $123.45
            'balance_locked_units'   => 5000,   // => $50.00
        ]);

        // Create asset balances
        $btc = Asset::factory()->create([
            'user_id' => $user->id,
            'symbol'  => 'BTC',
            'amount'  => 0.5,
            'locked_amount' => 0.1,
        ]);

        $eth = Asset::factory()->create([
            'user_id' => $user->id,
            'symbol'  => 'ETH',
            'amount'  => 10,
            'locked_amount' => 1,
        ]);

        // Authenticate user
        $this->actingAs($user);

        // Call the endpoint
        $response = $this->getJson('/api/profile')
            ->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'email',
                'email_verified',
                'balance' => [
                    'currency',
                    'available' => [
                        'major',
                        'minor',
                        'formatted',
                        'currency'
                    ],
                    'locked' => [
                        'major',
                        'minor',
                        'formatted',
                        'currency'
                    ],
                ],
                'assets',
                'created_at',
                'updated_at'
            ]);

        // Decode JSON for exact value assertions
        $data = $response->json();

        // --- Basic checks ---
        $this->assertEquals($user->id, $data['id']);
        $this->assertEquals('Trader 1', $data['name']);
        $this->assertEquals('trader1@example.com', $data['email']);
        $this->assertTrue($data['email_verified']);

        // --- Balance checks ---
        $this->assertEquals('USD', $data['balance']['currency']);

        // MoneyCast -> major units
        $this->assertEquals(123.45, $data['balance']['available']['major']);
        $this->assertEquals(50.00, $data['balance']['locked']['major']);

        // --- Asset checks ---
        $this->assertCount(2, $data['assets']);

        $symbols = collect($data['assets'])->pluck('symbol')->all();
        $this->assertEqualsCanonicalizing(['BTC', 'ETH'], $symbols);
    }
}
