<?php

namespace App\Exceptions;

use DomainException;

class AppDomainException extends DomainException
{
    protected $error = 'domain_error';

    public function toResponse()
    {
        return response()->json([
            'error' => $this->error,
            'message' => $this->getMessage(),
        ], $this->code);
    }
}
