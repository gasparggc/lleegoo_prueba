<?php 
namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Component\HttpClient\Response\MockResponse;

class FlightControllerTest extends WebTestCase
{
    public function testAvailEndpoint(): void
    {
        $client = static::createClient();

        $xmlFilePath = __DIR__ . '/../../Fixtures/MAD_BIO_OW_1PAX_RS_SOAP.xml';
        
        $this->assertFileExists($xmlFilePath, 'The fixtures XML file does not exist.');
    
        $client->request('GET', '/api/avail?origin=MAD&destination=BIO&date=2022-06-01');
    
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Expected status code 200.');
    
        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content, 'Response should be a JSON array.');
        $this->assertNotEmpty($content, 'Response should not be empty.');
    
        $this->assertArrayHasKey('originCode', $content[0]);
        $this->assertEquals('MAD', $content[0]['originCode'], 'The origin of the first flight should be MAD.');
    }

    public function testAvailEndpointWithInvalidParams(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/avail?origin=INVALID&destination=BIO&date=invalid-date');

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), 'Expected status code 400 for invalid parameters.');

        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content, 'Response should return an error response as a JSON array.');
        $this->assertArrayHasKey('error', $content, 'Expected an error key in the response for invalid parameters.');
    }

    public function testAvailEndpointNoResults(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/avail?origin=XYZ&destination=ABC&date=2024-01-01');

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Expected status code 200.');

        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content, 'Response should be a JSON array.');
        $this->assertEmpty($content, 'Response should be empty when no flights are available.');
    }
    

}
