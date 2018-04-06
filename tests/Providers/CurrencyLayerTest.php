<?php

namespace Tests\Currency\Captain\Providers;

use Currency\Captain\Providers\CurrencyLayer;
use PHPUnit\Framework\TestCase;

/**
 * Class CurrencyLayerTest
 * @package Tests\Currency\Captain\Providers
 * @group CurrencyLayer
 */
class CurrencyLayerTest extends TestCase
{
    protected $currencyLayer;

    public function setUp()
    {
        $currencyLayer = new CurrencyLayer(null, null, '3c2dfdae43b5c8f83b4ef78fa5997480');
        $this->setCurrencyLayer($currencyLayer);
    }


    public function testGetConversionRate()
    {
        $rate = $this->getCurrencyLayer()->getConversionRate('EUR', 'TRY');
        static::assertTrue(is_float($rate));

    }

    public function testGetCurrencyList()
    {
        $currencyList = $this->getCurrencyLayer()->getCurrencyList();
        static::assertContains('USD', $currencyList);
        static::assertContains('TRY', $currencyList);
    }


    /**
     * @param CurrencyLayer $currencyLayer
     * @return CurrencyLayerTest
     */
    private function setCurrencyLayer(CurrencyLayer $currencyLayer)
    {
        $this->currencyLayer = $currencyLayer;
        return $this;
    }

    /**
     * @return CurrencyLayer
     */
    private function getCurrencyLayer()
    {
        return $this->currencyLayer;
    }
}