<?php

namespace SendKuy\Exceptions;

final class SignatureVerificationException extends SendKuyException
{
    public function __construct(private readonly string $reason, string $message)
    {
        parent::__construct($message);
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
