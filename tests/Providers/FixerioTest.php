<?php

namespace Tests\Currency\Captain\Providers;

use Http\Mock\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Currency\Captain\Providers\Fixerio;

/**
 * Class FixerioTest
 * @package Tests\Currency\Captain\Providers
 * @group Fixerio
 */
class FixerioTest extends TestCase
{
    /** @var Fixerio */
    protected $fixerio;

    /**
     * Setup
     */
    public function setup()
    {
        $fixerio = new Fixerio();
        static::assertInstanceOf(Fixerio::class, $fixerio);
        $this->setFixerio($fixerio);
    }

    /**
     * Conversion Provider
     * @return array
     */
    public function conversionProvider()
    {
        return [
            ['USD', 'EUR'],
            ['USD', 'TRY'],
            ['USD', 'CAD']
        ];
    }

    /**
     * GetConversionRate Test
     * @dataProvider conversionProvider
     * @param string $to
     * @param string $from
     */
    public function testGetConversionRate(string $to, string $from)
    {
        $rate = $this->getFixerio()->getConversionRate($to, $from);
        static::assertTrue(is_float($rate));
    }

    /**
     * GetConversionRate2 Test
     */
    public function testGetConversionRate2()
    {
        $rate = $this->getFixerio()->getConversionRate('usd', 'try');
        static::assertNull($rate);
    }

    public function testGetConversionRate3()
    {
        $mockClient = new Client();
        /** @var ResponseInterface $response */
        $response = $this->createMock(ResponseInterface::class);
        $mockClient->setDefaultResponse($response);
        $fixerio = new Fixerio(null, $mockClient);
        $rate = $fixerio->getConversionRate('usd', 'try');
        static::assertNull($rate);
    }


    public function testGetCurrencyList()
    {
        $currencyList = $this->getFixerio()->getCurrencyList();
        static::assertTrue(is_array($currencyList));
    }


    public function testGetCurrencyList2()
    {
        $mockClient = new Client();
        $exception = new \Exception('No good');
        $mockClient->setDefaultException($exception);

        $fixerio = new Fixerio(null, $mockClient);
        $currencyList = $fixerio->getCurrencyList();
        static::assertTrue(is_array($currencyList));
    }

    public function testGetCurrencyList3()
    {
        $mockClient = new Client();
        $response = $this->createMock(ResponseInterface::class);
        $mockClient->setDefaultResponse($response);

        $fixerio = new Fixerio(null, $mockClient);
        $currencyList = $fixerio->getCurrencyList();
        static::assertTrue(is_array($currencyList));
    }


    public function testGetCurrencyList4()
    {
        $mockClient = new Client();
        $response = new Response(200, [], json_encode(['A'=>['B','C','D']]));
        $mockClient->setDefaultResponse($response);
        $fixerio = new Fixerio(null, $mockClient);
        $currencyList = $fixerio->getCurrencyList();
        static::assertTrue(is_array($currencyList));

    }


    /**
     * @return Fixerio
     */
    public function getFixerio() : Fixerio
    {
        return $this->fixerio;
    }

    /**
     * @param Fixerio $fixerio
     * @return FixerioTest
     */
    public function setFixerio(Fixerio $fixerio) : FixerioTest
    {
        $this->fixerio = $fixerio;
        return $this;
    }


}