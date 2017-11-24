<?php

namespace Currency\Captain\Providers;

/**
 * Interface ProviderInterface
 * @package Currency\Captain\Providers
 */
interface ProviderInterface
{
    /**
     * @param string $from
     * @param string $to
     * @return float
     */
    public function convert(string $from, string $to) : float;
}
