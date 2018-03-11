<?php

namespace Currency\Captain\Traits;

use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;

/**
 * Trait HasHttpClient
 * @package Currency\Captain\Traits
 */
trait HasHttpClient
{
    /** @var HttpClient $client */
    protected $client = null;

    /**
     * @return HttpClient
     */
    protected function getClient() : HttpClient
    {
        if (is_null($this->client)) {
            $client = HttpClientDiscovery::find();
            $this->setClient($client);
        }
        return $this->client;
    }

    /**
     * @param HttpClient $client
     * @return $this
     */
    public function setClient(HttpClient $client)
    {
        $this->client = $client;
        return $this;
    }
}
