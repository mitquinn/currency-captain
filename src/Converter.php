<?php

namespace Currency\Captain;

use Currency\Captain\Providers\Fixerio;
use Currency\Captain\Providers\ProviderInterface;

/**
 * Class Converter
 * @package Currency\Captain
 */
class Converter
{
    /** @var  ProviderInterface $provider */
    protected $provider;

    public function __construct(ProviderInterface $provider = null)
    {
        if (is_null($provider)) {
            $provider = new Fixerio();
        }
        $this->setProvider($provider);
    }

    public function convert(string $from, string $to) : float
    {
        return $this->getProvider()->convert($from, $to);
    }

    public function setProvider(ProviderInterface $provider)
    {
        $this->provider = $provider;
        return $this;
    }

    public function getProvider()
    {
        return $this->provider;
    }

}
