<?php

namespace App\Exceptions;

use Illuminate\Http\Response;

class InsufficientBalanceException extends AppDomainException
{
    protected $error = 'insufficient_balance';
    protected $message = 'Insufficient balance.';
    protected $code = Response::HTTP_UNPROCESSABLE_ENTITY;
}
