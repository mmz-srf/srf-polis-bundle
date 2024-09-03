<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use SRF\PolisBundle\Model\Polis\PolisVotation;
use SRF\PolisBundle\Service\PolisClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class PolisClientTest extends TestCase
{
    public function testPolisClientTransformsApiDataCorrectly(): void
    {
        $id = '5007';
        $testData = file_get_contents(__DIR__.'/Mocks/votation_'.$id.'.json');
        $response = new MockResponse($testData);
        $httpClient = new MockHttpClient($response);
        $polisClient = new PolisClient($httpClient);
        $votation = $polisClient->fetchPolisVotationById($id, 'de');
        $this->assertInstanceOf(PolisVotation::class, $votation);
        $this->assertEquals($id, $votation->id);
    }
}
