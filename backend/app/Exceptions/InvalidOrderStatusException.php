<?php
namespace App\Exceptions;

use Illuminate\Http\Response;

class InvalidOrderStatusException extends AppDomainException
{
    protected $error   = 'invalid_order_status';
    protected $message = 'Invalid order status.';
    protected $code    = Response::HTTP_UNPROCESSABLE_ENTITY;
}
