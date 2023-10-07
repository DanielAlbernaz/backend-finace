<?php

namespace App\Exceptions;

use Exception;

class InvalidContextException extends Exception
{
    private $context;
    public function __construct(string $context)
    {
        $this->context = $context;
        $this->message = "Context for '{$this->context}' is not declared on this service";
    }

    public function render()
    {
        return response()->json("Este serviço não declarou contexto para '{$this->context}'");
    }
}
