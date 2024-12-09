<?php

namespace App\Infrastructure\Http;

use DateTime;
use DOMXPath;
use DOMDocument;
use Psr\Log\LoggerInterface;
use App\Domain\Entity\Segment;
use App\Application\Port\FlightPort;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SoapFlightAdapter implements FlightPort
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    public function fetchFlights(string $origin, string $destination, string $date): array
    {
        $url = $_ENV['SOAP_API_URL'];

        try {
            $response = $this->httpClient->request('GET', $url, [
                'query' => [
                    'origin' => $origin,
                    'destination' => $destination,
                    'date' => $date,
                ],
            ]);

            $content = $response->getContent();

            if (strpos($content, '<error>') !== false) {
                throw new \Exception('Internal Server Error');
            }

            $dom = new DOMDocument();
            $dom->loadXML($content);

            $xpath = new DOMXPath($dom);

            $xpath->registerNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
            $xpath->registerNamespace('ns', 'http://www.iata.org/IATA/EDIST/2017.2');

            $flightSegments = $xpath->query('//ns:AirShoppingRS/ns:DataLists/ns:FlightSegmentList/ns:FlightSegment');

            $segments = [];
            foreach ($flightSegments as $flight) {
                $segment = new Segment();
                $segment->setOriginCode($xpath->evaluate('string(ns:Departure/ns:AirportCode)', $flight));
                $segment->setOriginName($xpath->evaluate('string(ns:Departure/ns:AirportName)', $flight));
                $segment->setDestinationCode($xpath->evaluate('string(ns:Arrival/ns:AirportCode)', $flight));
                $segment->setDestinationName($xpath->evaluate('string(ns:Arrival/ns:AirportName)', $flight));
                $segment->setStart(new DateTime(
                    $xpath->evaluate('string(ns:Departure/ns:Date)', $flight) . ' ' .
                    $xpath->evaluate('string(ns:Departure/ns:Time)', $flight)
                ));
                $segment->setEnd(new DateTime(
                    $xpath->evaluate('string(ns:Arrival/ns:Date)', $flight) . ' ' .
                    $xpath->evaluate('string(ns:Arrival/ns:Time)', $flight)
                ));
                $segment->setTransportNumber($xpath->evaluate('string(ns:MarketingCarrier/ns:FlightNumber)', $flight));
                $segment->setCompanyCode($xpath->evaluate('string(ns:OperatingCarrier/ns:AirlineID)', $flight));
                $segment->setCompanyName($xpath->evaluate('string(ns:OperatingCarrier/ns:Name)', $flight));

                $segments[] = $segment;
            }

            return $segments;
        } catch (\Exception $e) {
            $this->logger->error('Error while fetching flights', ['exception' => $e->getMessage()]);
            throw $e;
        }
    }
}
