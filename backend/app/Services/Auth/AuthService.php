<?php
namespace App\Services\Auth;

use App\Exceptions\Auth\EmailNotVerifiedException;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AuthService
{
    public const API_LOGIN_TOKEN_NAME = 'userLoginToken';

    public function __construct(private UserRepository $userRepository)
    {}

    public function login(string $email, string $password): User
    {
        $user = $this->userRepository->findByEmail($email);

        if (! $user) {
            throw new InvalidCredentialsException();
        }

        if (! $user->hasVerifiedEmail()) {
            throw new EmailNotVerifiedException();
        }

        $credentials = [
            'email'    => $email,
            'password' => $password,
        ];

        if (! Auth::attempt($credentials)) {
            throw new InvalidCredentialsException();
        }

        // Successful login: Return the user object
        return $user;
    }

    public function generateAccessToken(User $user): string
    {
        return $user->createToken(self::API_LOGIN_TOKEN_NAME)->plainTextToken;
    }

    public function logout(User $user)
    {
        $user->currentAccessToken()?->delete();
        Session::invalidate();
        Session::regenerateToken();
    }
}
