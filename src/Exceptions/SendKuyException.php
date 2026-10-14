<?php

namespace SendKuy\Exceptions;

use RuntimeException;
use Throwable;

class SendKuyException extends RuntimeException
{
    public function __construct(
        string $message = '',
        private readonly int $httpStatus = 0,
        private readonly ?string $errorType = null,
        private readonly ?string $errorCode = null,
        private readonly ?string $param = null,
        private readonly ?string $docUrl = null,
        private readonly ?string $requestId = null,
        private readonly ?int $retryAfter = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $httpStatus, $previous);
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function getErrorType(): ?string
    {
        return $this->errorType;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getParam(): ?string
    {
        return $this->param;
    }

    public function getDocUrl(): ?string
    {
        return $this->docUrl;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
