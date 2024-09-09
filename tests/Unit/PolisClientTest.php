<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use SRF\PolisBundle\Model\Polis\PolisVotation;
use SRF\PolisBundle\Service\PolisClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class PolisClientTest extends TestCase
{
    /**
     * @dataProvider polisCaseDataProvider
     */
    public function testPolisClientTransformsApiDataCorrectly($caseId, $data): void
    {
        $response = new MockResponse($data);
        $httpClient = new MockHttpClient($response);
        $polisClient = new PolisClient($httpClient);
        $votation = $polisClient->fetchPolisVotationById($caseId, 'de');
        $this->assertInstanceOf(PolisVotation::class, $votation);
        $this->assertEquals($caseId, $votation->id);
    }

    public function polisCaseDataProvider(): \Generator
    {
        $cases = ['1591', '5006', '5007'];
        foreach ($cases as $caseId) {
            yield [$caseId, file_get_contents(__DIR__.'/Mocks/votation_'.$caseId.'.json')];
        }
    }
}
