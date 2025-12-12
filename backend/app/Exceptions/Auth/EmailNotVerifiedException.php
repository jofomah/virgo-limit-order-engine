<?php

namespace App\Exceptions\Auth;

use Illuminate\Http\Response;

class EmailNotVerifiedException extends AuthException
{
    protected $message = 'Please verify your email address before logging in.';
    protected $code = Response::HTTP_FORBIDDEN;
}
