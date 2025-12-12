<?php

namespace App\Exceptions\Auth;

use App\Exceptions\AppDomainException;
use Illuminate\Http\Response;

class AuthException extends AppDomainException
{
    protected $error = 'auth_error';
    protected $code = Response::HTTP_UNAUTHORIZED;
}
