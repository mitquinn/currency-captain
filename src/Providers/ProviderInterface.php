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
     * @return float|null
     */
    public function getConversionRate(string $from, string $to) : ?float;

    /**
     * @return array
     */
    public function getCurrencyList() : array;

}
