<?php

namespace App\Exceptions\Auth;

class InvalidCredentialsException extends AuthException
{
    protected $message = 'The provided credentials are invalid.';
    protected $code = 401; // HTTP Unauthorized
}
