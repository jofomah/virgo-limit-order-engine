<?php
namespace App\Exceptions\Auth;

use Illuminate\Http\Response;

class InvalidCredentialsException extends AuthException
{
    protected $message = 'The provided credentials are invalid.';
    protected $code    = Response::HTTP_UNAUTHORIZED;
}
