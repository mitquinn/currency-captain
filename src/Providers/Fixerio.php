<?php

namespace Currency\Captain\Providers;

use Monolog\Logger;
use Http\Client\HttpClient;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;
use PHPUnit\Runner\Exception;
use Currency\Captain\Traits\HasLogger;
use Psr\Http\Message\ResponseInterface;
use Currency\Captain\Traits\HasHttpClient;

/**
 * Class Fixerio
 * @package Currency\Captain\Providers
 * @see https://fixer.io/
 * @todo Fixer.io has upgraded there API. Must upgrade provider to new API endpoints.
 */
class Fixerio implements ProviderInterface
{
    /** HasLogger */
    use HasLogger;

    /** HasHttpClient */
    use HasHttpClient;

    /**
     * Fixerio constructor.
     * @param LoggerInterface|null $logger
     * @param HttpClient|null $client
     */
    public function __construct(?LoggerInterface $logger = null, ?HttpClient $client = null)
    {
        if (is_null($logger)) {
            $logger = new Logger('Currency_Captain_Fixerio');
        }
        $this->setLogger($logger);

        if (is_null($client)) {
            $client = $this->getClient();
        }
        $this->setClient($client);
    }

    /**
     * Gets the conversion rate between two currencies.
     * @param string $from Base currency for conversion.
     * @param string $to Target currency for conversion.
     * @return float|null Conversion rate for the base to target currency.
     * @throws \Http\Client\Exception
     */
    public function getConversionRate(string $from, string $to) : ?float
    {
        $rate = null;
        $endpoint = "https://api.fixer.io/latest?base=$from&symbols=$to";
        $request = new Request('GET', $endpoint);
        try {
            $response = $this->getClient()->sendRequest($request);
            if ($response->getStatusCode() != 200) {
                throw new Exception("Fixerio - Returned non-200 response.");
            }
            $rate = $this->parseConversionRateResponse($response, $to);
        } catch (\Exception $exception) {
            $this->getLogger()->error($exception->getMessage());
        }
        return $rate;
    }

    /**
     * Returns a pseudo currency list.
     * Fixerio does not seem to have an endpoint for a full currency list.
     * @return array
     * @throws \Http\Client\Exception
     */
    public function getCurrencyList() : array
    {
        $currencyList = array();
        $endpoint = "https://api.fixer.io/latest?base=USD";
        $request = new Request('GET', $endpoint);
        try {
            $response = $this->getClient()->sendRequest($request);
            if ($response->getStatusCode() != 200) {
                throw new Exception("Fixerio - Returned non-200 response.");
            }
            $currencyList = $this->parseCurrencyListResponse($response);
        } catch (\Exception $exception) {
            $this->getLogger()->error($exception->getMessage());
        }
        return $currencyList;
    }

    /**
     * Parses the Fixerio response to get the returned rate.
     * @param ResponseInterface $response
     * @param string $to Target conversion currency ccy.
     * @return float
     * @throws \Exception
     */
    private function parseConversionRateResponse(ResponseInterface $response, string $to) : float
    {
        $contents = $response->getBody()->getContents();
        $decoded = json_decode($contents);
        if (isset($decoded->rates->$to)) {
            return $decoded->rates->$to;
        }
        throw new \Exception('Fixerio - Conversion rate not found.');
    }

    /**
 * Parses the Fixerio list building a list of currency ccy. Also manually adds EUR.
     * @param ResponseInterface $response
     * @return array
     * @throws \Exception
     */
    private function parseCurrencyListResponse(ResponseInterface $response) : array
    {
        $contents = $response->getBody()->getContents();
        $decoded = json_decode($contents);
        if (isset($decoded->rates)) {
            $currencyList = array_keys((array)$decoded->rates);
            $currencyList[] = 'USD';
            return $currencyList;
        }
        throw new \Exception('Fixerio - Rates not found.');
    }
}
