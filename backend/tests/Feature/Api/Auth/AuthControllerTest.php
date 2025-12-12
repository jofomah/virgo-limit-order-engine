<?php
namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    // Use RefreshDatabase to reset the DB state after each test
    use RefreshDatabase;

    // Define the password used by the factory for testing purposes
    private const TEST_PASSWORD = 'test1234';
    private const LOGIN_URL = '/api/auth/login';
    private const LOGOUT_URL = '/api/auth/logout';

    protected function setUp(): void
    {
        parent::setUp();
    }


    public function test_a_verified_user_can_login_successfully()
    {
        $user = User::factory()->verified()->create([
            'password' => Hash::make(self::TEST_PASSWORD),
        ]);

        $response = $this->postJson(self::LOGIN_URL, [
            'email'    => $user->email,
            'password' => self::TEST_PASSWORD,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure(['access_token', 'user' => ['id', 'email']]);
        $this->assertAuthenticatedAs($user, 'sanctum');
    }

    public function test_unverified_user_cannot_login_and_gets_a_403_forbidden()
    {
        $user = User::factory()->unverified()->create([
            'password' => Hash::make(self::TEST_PASSWORD),
        ]);

        $response = $this->postJson(self::LOGIN_URL, [
            'email' => $user->email,
            'password' => self::TEST_PASSWORD,
        ]);

        // ASSERT: Must fail with 403 Forbidden due to EmailNotVerifiedException
        $response->assertStatus(Response::HTTP_FORBIDDEN); 
        $this->assertGuest('sanctum'); // User must NOT be authenticated
    }

    public function test_login_fails_with_invalid_password_and_returns_401_unauthorized()
    {
        $user = User::factory()->verified()->create([
             'password' => Hash::make(self::TEST_PASSWORD),
        ]);

        $response = $this->postJson(self::LOGIN_URL, [
            'email' => $user->email,
            'password' => 'a-bad-password', // Incorrect password
        ]);

        // ASSERT: Must fail with 401 Unauthorized due to InvalidCredentialsException
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $this->assertGuest('sanctum');
    }

    public function test_login_fails_if_user_does_not_exist_and_returns_401_unauthorized()
    {
        $response = $this->postJson(self::LOGIN_URL, [
            'email' => 'nonexistent@test.com',
            'password' => self::TEST_PASSWORD,
        ]);

        // ASSERT: Must fail with 401 Unauthorized (to prevent email enumeration)
        $response->assertStatus(Response::HTTP_UNAUTHORIZED); 
        $this->assertGuest('sanctum');
    }

    public function test_authenticated_user_can_logout_and_revoke_token()
    {
        $user = User::factory()->verified()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        // ACT: Send logout request with the token in the header
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson(self::LOGOUT_URL);

        // ASSERT: Check response
        $response->assertStatus(Response::HTTP_OK)
                 ->assertJson(['message' => 'User logged out successfully.']);

        // ASSERT: Verify the token was revoked (logout logic success)
        // Attempting to access a protected route with the old token should fail
        $this->assertDatabaseMissing('personal_access_tokens', [
            'token' => hash('sha256', $token) 
        ]);

        // This forces the framework to re-authenticate the next request 
        // by checking the (now-deleted) token against the database.
        $this->refreshApplication();
    
        $failureResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
                            ->postJson(self::LOGOUT_URL);

        $failureResponse->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
