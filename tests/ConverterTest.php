<?php

namespace Tests\Currency\Captain;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Currency\Captain\Converter;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Currency\Captain\Providers\Fixerio;
use Symfony\Component\Cache\Simple\ArrayCache;
use Currency\Captain\Providers\ProviderInterface;

/**
 * Class ConverterTest
 */
class ConverterTest extends TestCase
{

    /** @var  Converter $converter */
    protected $converter;

    /**
     * Setup
     */
    public function setup()
    {
        $logger = new Logger('PHPUnit');
        static::assertInstanceOf(LoggerInterface::class, $logger);
        $provider = new Fixerio($logger);
        static::assertInstanceOf(ProviderInterface::class, $provider);
        $cachehandler = new ArrayCache();
        static::assertInstanceOf(CacheInterface::class, $cachehandler);
        $converter = new Converter($logger, $provider, $cachehandler);
        $converter->setCacheTime(200);
        $this->setConverter($converter);
    }

    /**
     * Tests for getConversion.
     * @param string $from
     * @param string $to
     * @dataProvider conversionProvider
     */
    public function testGetConversion(string $from, string $to)
    {
        $rate = $this->getConverter()->getConversionRate($from, $to);
        if(is_null($rate)) {
            static::assertNull($rate);
        } else {
            static::assertTrue(is_float($rate));
        }
        $rate = $this->getConverter()->getConversionRate($from, $to);
        if(is_null($rate)) {
            static::assertNull($rate);
        } else {
            static::assertTrue(is_float($rate));
        }
    }

    /**
     * Conversion Provider
     * @return array
     */
    public function conversionProvider() : array
    {
        return [
            ['USD', 'EUR'],
            ['usd', 'try'],
            ['usd', 'cad'],
            ['usd', 'usd'],
            ['usd', 'nan']
        ];
    }

    /**
     * Tests the getCurrencyList function.
     */
    public function testGetCurrencyList()
    {
        $currencyList = $this->getConverter()->getCurrencyList();
        static::assertContains('USD', $currencyList);
        static::assertContains('EUR', $currencyList);
        static::assertContains('TRY', $currencyList);
        $currencyList = $this->getConverter()->getCurrencyList();
        static::assertContains('USD', $currencyList);
        static::assertContains('EUR', $currencyList);
        static::assertContains('TRY', $currencyList);
    }


    /**
     * Tests for convert.
     * @param float $amount
     * @param string $from
     * @param string $to
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @dataProvider convertProvider
     */
    public function testConvert(float $amount, string $from, string $to)
    {
        $amount = $this->getConverter()->convert($amount, $from, $to);
        if(is_null($amount)) {
            static::assertNull($amount);
        } else {
            static::assertTrue(is_float($amount));
        }
    }

    /**
     * Convert Provider
     * @return array
     */
    public function convertProvider() : array
    {
        return [
            [1.00, 'USD', 'EUR'],
            [1.00, 'USD', 'TRY'],
            [1.00, 'USD', 'CAD'],
            [1.00, 'USD', 'EUR'],
            [1.00, 'TRY', 'EUR'],
            [1.00, 'EUR', 'TRY'],
            [1.00, 'CAD', 'USD'],
            [1.00, 'GBP', 'EUR'],
            [1.00, 'USD', 'NAN']
        ];
    }


    /**
     * Tests for getAlpha3ByCountryCode.
     * @param string $countryCode
     * @param string $alpha3
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @dataProvider countryCodeProvider
     */
    public function testGetAlpha3ByCountryCode(string $countryCode, string $alpha3)
    {
        $result = $this->getConverter()->getAlpha3ByCountryCode($countryCode);
        static::assertTrue(is_string($result));
        static::assertEquals($alpha3, $result);
        $result2 = $this->getConverter()->getAlpha3ByCountryCode($countryCode);
        static::assertTrue(is_string($result2));
        static::assertEquals($alpha3, $result2);
    }


    /**
     * Country Code Providers.
     * @return array
     */
    public function countryCodeProvider()
    {
        return [
            ['US', 'USD'],
            ['TR', 'TRY'],
            ['FR', 'EUR'],
            ['JP', 'JPY']
        ];
    }


    /**
     * Tests for getAlpha3ByCountryCode.
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testGetAlpha3ByCountryCode2()
    {
        $result = $this->getConverter()->getAlpha3ByCountryCode('TEST');
        static::assertNull($result);
    }


    /**
     * Tests for getCurrencyByCountryCode.
     * @param string $countryCode
     * @param string $currencyName
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @dataProvider countryCurrencyProvider
     */
    public function testGetCurrencyByCountryCode(string $countryCode,    $currencyName)
    {
        $result = $this->getConverter()->getCurrencyByCountryCode($countryCode);
        static::assertEquals($currencyName, $result);
        $result2 = $this->getConverter()->getCurrencyByCountryCode($countryCode);
        static::assertEquals($currencyName, $result2);
    }


    /**
     * Currency Name Provider.
     * @return array
     */
    public function countryCurrencyProvider()
    {
        return [
            ['US', 'US Dollar'],
            ['FR', 'Euro'],
            ['JP', 'Japanese Yen'],
            ['TR', 'Turkish Lira'],
            ['WQ', null]

        ];
    }


    /**
     * Tests for getCurrencySymbol.
     * @dataProvider currencySymbolProvider
     */
    public function testGetCurrencySymbol(string $alpha3, $locale, string $symbol)
    {
        $result = $this->getConverter()->getCurrencySymbolByAlpha3($alpha3, $locale);
        static::assertEquals($symbol, $result);
    }


    /**
     * Alpha3 Symbol Provider.
     * @return array
     */
    public function currencySymbolProvider()
    {
        return [
            ['USD', 'en_US', '$'],
            ['EUR', 'en_US', '€'],
            ['TRY', 'tr_TR', '₺'],
            ['JPY', 'jp_JP', '¥'],
            ['USD', null, '$']
        ];
    }


    /**
     * Tests for getCurrencySymbolByCountryCode.
     * @param $countryCode
     * @param $locale
     * @param $symbol
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @dataProvider countrySymbolProvider
     */
    public function testGetCurrencySymbolByCountryCode(string $countryCode, $locale, string $symbol)
    {
        $result = $this->getConverter()->getCurrencySymbolByCountryCode($countryCode, $locale);
        static::assertEquals($symbol, $result);
    }


    /**
     * Country Symbol Provider.
     * @return array
     */
    public function countrySymbolProvider()
    {
        return [
            ['US', 'en_US', '$'],
            ['FR', 'en_US', '€'],
            ['TR', 'tr_TR', '₺'],
            ['JP', 'jp_JP', '¥']
        ];
    }


    /**
     * @param string $alpha3
     * @param string $locale
     * @param string $symbol
     * @dataProvider currencySymbolProvider
     */
    public function testGetCurrencySymbolByAlpha3(string $alpha3, $locale, string $symbol)
    {
        $result = $this->getConverter()->getCurrencySymbolByAlpha3($alpha3, $locale);
        static::assertEquals($symbol, $result);
        $result2 = $this->getConverter()->getCurrencySymbolByAlpha3($alpha3, $locale);
        static::assertEquals($symbol, $result2);
    }


    public function testConstructor()
    {
        $converter = new Converter();
        static::assertInstanceOf(Converter::class, $converter);
    }




    /**
     * Set the converter.
     * @param Converter $converter
     * @return ConverterTest
     */
    protected function setConverter(Converter $converter) : ConverterTest
    {
        static::assertInstanceOf(Converter::class, $converter);
        $this->converter = $converter;
        return $this;
    }

    /**
     * Returns the converter.
     * @return Converter
     */
    protected function getConverter() : Converter
    {
        $converter = $this->converter;
        static::assertInstanceOf(Converter::class, $converter);
        return $converter;
    }
}
