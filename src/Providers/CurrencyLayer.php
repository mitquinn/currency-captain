<?php

namespace Currency\Captain\Providers;

use Currency\Captain\Traits\HasHttpClient;
use Currency\Captain\Traits\HasLogger;
use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;


ini_set('xdebug.var_display_max_depth', 5);
ini_set('xdebug.var_display_max_children', 256);
ini_set('xdebug.var_display_max_data', 1024);

/**
 * Class CurrencyLayer
 * @package Currency\Captain\Providers
 */
class CurrencyLayer implements ProviderInterface
{
    /** HasLogger */
    use HasLogger;

    /** HasHttpClient */
    use HasHttpClient;

    /** @var string $key CurrencyLayer API key. */
    protected $key;

    /**
     * CurrencyLayer constructor.
     * @param LoggerInterface|null $logger
     * @param HttpClient|null $client
     * @param string $key
     */
    public function __construct(?LoggerInterface $logger = null, ?HttpClient $client = null, string $key)
    {
        if(is_null($logger)) {
            $logger = new Logger('Currency_Captain_CurrencyLayer');
        }
        $this->setLogger($logger);

        if(is_null($client)) {
            $client = $this->getClient();
        }
        $this->setClient($client);

        //Set the API Key.
        $this->setKey($key);
    }

    /**
     * CurrencyLayer does not support changing the base currency. So a little math is needed.
     * @param string $from
     * @param string $to
     * @return float|null
     * @throws \Http\Client\Exception
     */
    public function getConversionRate(string $from, string $to) : ?float
    {
        $rate = null;
        $key = $this->getKey();
        $endPoint = "http://apilayer.net/api/live?access_key=$key&format=1";
        $request = new Request('GET', $endPoint);
        try {
            $response = $this->getClient()->sendRequest($request);
            if ($response->getStatusCode() != 200) {
                throw new Exception("CurrencyLayer - Returned non-200 response.");
            }
            $rate = $this->parseConversionRateResponse($response, $from, $to);
        } catch (\Exception $exception) {
            $this->getLogger()->error($exception->getMessage());
        }
        return $rate;
    }

    /**
     * The base currency must be USD.
     * @return array
     * @throws \Http\Client\Exception
     */
    public function getCurrencyList() : array
    {

        $key = $this->getKey();
        $endPoint = "http://apilayer.net/api/live?access_key=$key&format=1";
        $request = new Request('GET', $endPoint);
        try {
            $response = $this->getClient()->sendRequest($request);
            if ($response->getStatusCode() != 200) {
                throw new \Exception("CurrencyLayer - Returned non-200 response.");
            }
            return $this->parseCurrencyListResponse($response);
        } catch (\Exception $exception) {
            $this->getLogger()->error($exception->getMessage());
        }
    }

    /**
     * @param string $key
     * @return CurrencyLayer
     */
    public function setKey(string $key): CurrencyLayer
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return string
     */
    protected function getKey()
    {
        return $this->key;
    }


    /**
     * @param ResponseInterface $response
     * @param string $from
     * @param string $to
     * @return float
     * @throws \Exception
     */
    private function parseConversionRateResponse(ResponseInterface $response, string $from, string $to) : float
    {
        $contents = $response->getBody()->getContents();
        $decoded = json_decode($contents);
        $from = 'USD'.$from;
        $to = 'USD'.$to;


        if(!isset($decoded->quotes)) {
            throw new \Exception('CurrencyLayer - Conversion rates not found.');
        }

        $rates = $decoded->quotes;
        if(!isset($rates->$from) or !isset($rates->$to)) {
            throw new \Exception("CurrencyLayer - Conversion rate not found for $from -> $to");
        }

        return $rates->$to / $rates->$from;

    }


    /**
     * @param ResponseInterface $response
     * @return array
     * @throws \Exception
     */
    private function parseCurrencyListResponse(ResponseInterface $response) : array
    {
        $contents = $response->getBody()->getContents();
        $decoded = json_decode($contents, true);
        if(isset($decoded['quotes'])) {
            $currencyList = array_keys($decoded['quotes']);
            foreach($currencyList as &$value) {
                $value = substr($value, 3, 3);
            }
            return $currencyList;
        }
        throw new \Exception('CurrencyLayer - Rates not found.');
    }
}