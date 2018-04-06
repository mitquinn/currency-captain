<?php

namespace Currency\Captain;

use Monolog\Logger;
use Alcohol\ISO4217;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Currency\Captain\Traits\HasLogger;
use Currency\Captain\Providers\Fixerio;
use Currency\Captain\Providers\ProviderInterface;
use Symfony\Component\Cache\Simple\FilesystemCache;

/**
 * Class Converter
 * @package Currency\Captain
 */
class Converter
{
    /** HasLogger */
    use HasLogger;

    /** @var int $cacheTime Three hour cache time. */
    protected $cacheTime = 10800;

    /** @var  ProviderInterface $provider */
    protected $provider;

    /** @var  CacheInterface $cachehandler */
    protected $cachehandler;

    /** @var string $locale */
    protected $locale;

    /** @var ISO4217 $iso4217 */
    protected $iso4217;


    /**
     * Converter constructor.
     * @param LoggerInterface|null $logger
     * @param ProviderInterface|null $provider
     * @param CacheInterface|null $cachehandler
     * @param string|null $locale
     */
    public function __construct(
        LoggerInterface $logger = null,
        ProviderInterface $provider = null,
        CacheInterface $cachehandler = null,
        string $locale = null
    ) {
        if (is_null($logger)) {
            $logger = new Logger('Currency_Captain_Logger');
        }
        $this->setLogger($logger);

        if (is_null($provider)) {
            $provider = new Fixerio($this->getLogger());
        }
        $this->setProvider($provider);

        if (is_null($cachehandler)) {
            $cachehandler = new FilesystemCache('Currency_Captain_Cache');
        }
        $this->setCachehandler($cachehandler);

        if(is_null($locale)) {
            $locale = 'en_US';
        }
        $this->setLocale($locale);

        $this->iso4217 = new ISO4217();
    }


    /**
     * Returns the conversion rate between two currencies.
     * @param string $from
     * @param string $to
     * @return float|null
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getConversionRate(string $from, string $to) : ?float
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            $this->getLogger()
                ->notice("getConversionRate - trying to get conversion rate $from -> $to, possible error.");
            return 1.00;
        }

        //If cachehandler has key then we will return the key.
        if ($this->getCachehandler()->has($from.$to)) {
            return $this->getCachehandler()->get($from.$to);
        }

        $conversionRate = $this->getProvider()->getConversionRate($from, $to);

        if (is_null($conversionRate)) {
            $this->getLogger()->warning("getConversionRate - Conversion Rate is null.");
            return null;
        }

        $this->getCachehandler()->set($from.$to, $conversionRate, $this->getCacheTime());
        return $conversionRate;
    }


    /**
     * Converts an amount from one currency to another.
     * @param float $amount
     * @param string $from
     * @param string $to
     * @return float|null
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function convert(float $amount, string $from, string $to) : ?float
    {
        $rate = $this->getConversionRate($from, $to);

        if (is_null($rate)) {
            $this->getLogger()->warning('convert - Conversion rate is null.');
            return null;
        }

        return $amount * $rate;
    }


    /**
     * Returns a list of currencies from the provider.
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getCurrencyList() : array
    {
        if ($this->getCachehandler()->has('currencyList')) {
            return $this->getCachehandler()->get('currencyList');
        }

        $currencyList = $this->getProvider()->getCurrencyList();
        $this->getCachehandler()->set('currencyList', $currencyList, $this->getCacheTime());
        return $currencyList;
    }


    /**
     * Returns the alpha3 for currency by country code.
     * @param string $countryCode
     * @return null|string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getAlpha3ByCountryCode(string $countryCode) : ?string
    {
        $countryCode = strtoupper($countryCode);
        $cacheKey = $countryCode.'_Alpha3';

        if ($this->getCachehandler()->has($cacheKey)) {
            return $this->getCachehandler()->get($cacheKey);
        }

        $currencies = $this->getIso4217()->getAll();
        foreach ($currencies as $currency) {
            if(is_array($currency['country'])) {
                if (in_array($countryCode, $currency['country'])) {
                    $this->getCachehandler()->set($cacheKey, $currency['alpha3'], $this->getCacheTime());
                    return $currency['alpha3'];
                }
            }
            if ($currency['country'] == $countryCode) {
                $this->getCachehandler()->set($cacheKey, $currency['alpha3'], $this->getCacheTime());
                return $currency['alpha3'];
            }
        }
        return null;
    }


    /**
     * @param string $countryCode
     * @return null|string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getCurrencyByCountryCode(string $countryCode) : ?string
    {
        $countryCode = strtoupper($countryCode);
        $cacheKey = $countryCode.'_Currency';

        if ($this->getCachehandler()->has($cacheKey)) {
            return $this->getCachehandler()->get($cacheKey);
        }

        $currencies = $this->getIso4217()->getAll();
        foreach ($currencies as $currency) {
            if(is_array($currency['country'])) {
                if (in_array($countryCode, $currency['country'])) {
                    $this->getCachehandler()->set($cacheKey, $currency['name'], $this->getCacheTime());
                    return $currency['name'];
                }
            }
            if ($currency['country'] == $countryCode) {
                $this->getCachehandler()->set($cacheKey, $currency['name'], $this->getCacheTime());
                return $currency['name'];
            }
        }
        return null;
    }


    /**
     * Returns the currency symbol by locale.
     * @param string $alpha3
     * @param null|string $locale
     * @return null|string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getCurrencySymbolByAlpha3(string $alpha3, ?string $locale = null) : ?string
    {
        if (is_null($locale)) {
            $locale = $this->getLocale();
        }

        $cacheKey = $alpha3.'_'.$locale.'_Symbol';
        if ($this->getCachehandler()->has($cacheKey)) {
            return $this->getCachehandler()->get($cacheKey);
        }

        $formatter = new \NumberFormatter($locale."@currency=$alpha3", \NumberFormatter::CURRENCY);
        $symbol = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);

        $this->getCachehandler()->set($cacheKey, $symbol, $this->getCacheTime());
        return $symbol;
    }


    /**
     * @param string $countryCode
     * @param string|null $locale
     * @return null|string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getCurrencySymbolByCountryCode(string $countryCode, ?string $locale = null) : ?string
    {
        $alpha3 = $this->getAlpha3ByCountryCode($countryCode);
        return $this->getCurrencySymbolByAlpha3($alpha3, $locale);
    }


    /**
     * Sets the ttl used by the cachehandler.
     * @param int $cacheTime
     * @return Converter
     */
    public function setCacheTime(int $cacheTime) : Converter
    {
        $this->cacheTime = $cacheTime;
        return $this;
    }

    /**
     * Returns the cache ttl.
     * @return int
     */
    protected function getCacheTime() : int
    {
        return $this->cacheTime;
    }

    /**
     * Sets the currency provider.
     * @param ProviderInterface $provider
     * @return $this
     */
    public function setProvider(ProviderInterface $provider) : Converter
    {
        $this->provider = $provider;
        return $this;
    }

    /**
     * Returns a currency provider.
     * @return ProviderInterface
     */
    protected function getProvider() : ProviderInterface
    {
        return $this->provider;
    }

    /**
     * Sets the cache handler.
     * @param CacheInterface $cachehandler
     * @return $this
     */
    public function setCachehandler(CacheInterface $cachehandler) : Converter
    {
        $this->cachehandler = $cachehandler;
        return $this;
    }

    /**
     * Returns the cachehandler.
     * @return CacheInterface
     */
    protected function getCachehandler() : CacheInterface
    {
        return $this->cachehandler;
    }

    /**
     * @return ISO4217
     */
    protected function getIso4217() : ISO4217
    {
        return $this->iso4217;
    }

    /**
     * @param string $locale
     * @return Converter
     */
    public function setLocale(string $locale) : Converter
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * @return string
     */
    protected function getLocale() : string
    {
        return $this->locale;
    }
}
