<?php

namespace SendKuy\Tests\Support;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

final class FakeNetworkException extends RuntimeException implements NetworkExceptionInterface
{
    public function __construct()
    {
        parent::__construct('Connection refused');
    }

    public function getRequest(): RequestInterface
    {
        return new Request('GET', 'https://app.sendkuy.com/api/v1');
    }
}
