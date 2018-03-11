<?php

namespace Tests\Currency\Captain\Providers;

use Currency\Captain\Traits\HasHttpClient;
use Http\Client\HttpClient;
use PHPUnit\Framework\TestCase;

class HasHttpClientTest extends TestCase
{
    use HasHttpClient;

    public function testGetClient()
    {
        $client = $this->getClient();
        static::assertInstanceOf(HttpClient::class, $client);
    }
}