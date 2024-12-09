<?php

namespace App\Tests\Infrastructure\Http;

use App\Infrastructure\Http\SoapFlightAdapter;
use App\Domain\Entity\Segment;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Psr\Log\LoggerInterface;

class SoapFlightAdapterTest extends TestCase
{
    public function testFetchFlights(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $xmlFilePath = __DIR__ . '/../../Fixtures/MAD_BIO_OW_1PAX_RS_SOAP.xml';
        $this->assertFileExists($xmlFilePath, 'El archivo XML de prueba no existe');
        $xmlResponse = file_get_contents($xmlFilePath);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn($xmlResponse);

        $httpClient->method('request')->willReturn($response);

        $adapter = new SoapFlightAdapter($httpClient, $logger);

        $segments = $adapter->fetchFlights('MAD', 'BIO', '2022-06-01');

        $this->assertCount(5, $segments);

        $segment = $segments[0];
        $this->assertInstanceOf(Segment::class, $segment);
        $this->assertEquals('MAD', $segment->getOriginCode());
        $this->assertEquals('Madrid Adolfo Suarez-Barajas', $segment->getOriginName());
        $this->assertEquals('BIO', $segment->getDestinationCode());
        $this->assertEquals('Bilbao', $segment->getDestinationName());
        $this->assertEquals(new \DateTime('2022-06-01 11:50'), $segment->getStart());
        $this->assertEquals(new \DateTime('2022-06-01 12:55'), $segment->getEnd());
        $this->assertEquals('0426', $segment->getTransportNumber());
        $this->assertEquals('IB', $segment->getCompanyCode());
        $this->assertEquals('Iberia', $segment->getCompanyName());
    }

    public function testFetchFlightsWithErrorResponse(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $responseError = $this->createMock(ResponseInterface::class);
        $responseError->method('getContent')->willReturn('<error>Internal Server Error</error>');
        $httpClient->method('request')->willReturn($responseError);

        $adapter = new SoapFlightAdapter($httpClient, $logger);

        try {
            $adapter->fetchFlights('MAD', 'BIO', '2022-06-01');
            $this->fail('Expected exception not thrown');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Internal Server Error', $e->getMessage());
        }
    }

    public function testFetchFlightsWithEmptyResponse(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $responseEmpty = $this->createMock(ResponseInterface::class);
        $responseEmpty->method('getContent')->willReturn('<flights></flights>');
        $httpClient->method('request')->willReturn($responseEmpty);

        $adapter = new SoapFlightAdapter($httpClient, $logger);

        $segments = $adapter->fetchFlights('MAD', 'BIO', '2022-06-01');
        $this->assertCount(0, $segments, 'Should handle empty flight results gracefully');
    }

    public function testFetchFlightsWithTimeout(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $httpClient->method('request')->willThrowException(new \Exception('Timeout occurred'));

        $adapter = new SoapFlightAdapter($httpClient, $logger);

        try {
            $adapter->fetchFlights('MAD', 'BIO', '2022-06-01');
            $this->fail('Expected timeout exception');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Timeout occurred', $e->getMessage());
        }
    }
}
