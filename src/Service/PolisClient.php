<?php

namespace SRF\PolisBundle\Service;

use SRF\PolisBundle\Model\Polis\PolisCantonalResult;
use SRF\PolisBundle\Model\Polis\PolisCase;
use SRF\PolisBundle\Model\Polis\PolisResult;
use SRF\PolisBundle\Model\Polis\PolisResultCondition;
use SRF\PolisBundle\Model\Polis\PolisVotation;
use SRF\PolisBundle\Model\Polis\PolisVoteLocation;
use SRF\PolisBundle\Model\Polis\PolisVoteType;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PolisClient
{
    /**
     * Client to fetch Polis data from ApiGee
     * Authentification by oauth is implemented with oauth2-http-bundle.
     *
     * @see https://github.com/BenjaminFavre/oauth2-http-client?tab=readme-ov-file#how-it-works
     */
    public function __construct(
        private HttpClientInterface $client,
    ) {
    }

    private function translatePolisLanguageCode(string $languageCode): string
    {
        return match ($languageCode) {
            'rm' => 'rr',
            default => $languageCode,
        };
    }

    public function fetchPolisCaseByVotationId(int $id, string $language): PolisCase
    {
        $votationResponse = $this->client->request('GET', sprintf('/polis-api/v2/votations/%s?lang=%s', $id, $this->translatePolisLanguageCode($language)));
        $data = $votationResponse->toArray();

        $case = [];
        foreach ($data['Items'] as &$item) {
            if ('tpc.eMedia.PolisV2.Business.API.v1.Models.Export.Case, tpc.eMedia.PolisV2.Business' === $item['__type']) {
                $case = $item;
            } elseif ('tpc.eMedia.PolisV2.Business.API.v1.Models.Export.Votation, tpc.eMedia.PolisV2.Business' === $item['__type']) {
                $item['VotationCantonalResults'] = [];

                $cantonalMajority = [
                    'yes' => 0,
                    'no' => 0,
                ];
                $allResults = [];
                foreach ($item['Results'] as $result) {
                    if ('Item3' === $result['dataConditionID']) {
                        foreach ($result['Result'] as $resultItem) {
                            $allResults[] = $resultItem;
                            if ($resultItem['Location']['id'] === $item['VotationLocation']['id']) {
                                $item['VotationMainResult'] = $resultItem;
                            }
                            // National:
                            if ('1' === $item['VotationLocation']['id']) {
                                if (($resultItem['Location']['ParentLocationID'] ?? null) === $item['VotationLocation']['id']) {
                                    $item['VotationCantonalResults'][] = $resultItem;
                                    if ($resultItem['ResultCondition']['id'] === PolisResultCondition::VOTATION_ACCEPTED->value) {
                                        $cantonalMajority['yes'] += $resultItem['Location']['ElectionPower'];
                                    } elseif ($resultItem['ResultCondition']['id'] === PolisResultCondition::VOTATION_DECLINED->value) {
                                        $cantonalMajority['no'] += $resultItem['Location']['ElectionPower'];
                                    }
                                }
                            }
                        }
                    }
                }

                // calc relatives
                $relatives = [
                    'yes' => 0 === $cantonalMajority['yes']
                        ? 0
                        : 100 / ($cantonalMajority['yes'] + $cantonalMajority['no']) * $cantonalMajority['yes'],
                    'no' => 0 === $cantonalMajority['no']
                        ? 0
                        : 100 / ($cantonalMajority['yes'] + $cantonalMajority['no']) * $cantonalMajority['no'],
                ];

                $item['cantonalMajority'] = [
                    'absolute' => $cantonalMajority,
                    'relative' => $relatives,
                ];

                $item['allFinalResults'] = $allResults;

                $case['Votations'] = [
                    'Votation' => [$item],
                ];
            }
        }

        return $this->denormalizePolisCase($case);
    }

    public function fetchPolisVotationById(int $id, string $language): PolisVotation
    {
        return $this->fetchPolisCaseByVotationId($id, $this->translatePolisLanguageCode($language))->votations[0];
    }

    public function fetchPolisCaseById(int $id, string $language = 'de'): PolisCase
    {
        $caseData = $this->client->request('GET', sprintf('/polis-api/v2/cases/%s?lang=%s', $id, $this->translatePolisLanguageCode($language)));
        $data = $caseData->toArray();

        return $this->denormalizePolisCase($data['Case'][0]);
    }

    public function fetchPolisCases(string $language = 'de', bool $onlyActive = true): array
    {
        $caseData = $this->client->request('GET', sprintf(
            '/polis-api/v2/cases?lang=%s&listAllCases=%s',
            $this->translatePolisLanguageCode($language),
            $onlyActive ? 'false' : 'true'
        ));
        $data = $caseData->toArray();

        return array_map(fn ($caseData) => $this->denormalizePolisCase($caseData), $data['Case'] ?? []);
    }

    private function denormalizePolisCase(array $caseData): PolisCase
    {
        return new PolisCase(
            id: $caseData['id'],
            title: $caseData['Title'],
            date: self::parsePolisDateTime($caseData['EventDate']),
            active: $caseData['active'],
            votations: array_map(fn ($votationData) => new PolisVotation(
                id: $votationData['id'],
                title: $votationData['Title'],
                individualTitle: $votationData['IndividualTitle'],
                location: PolisVoteLocation::createFromArray($votationData['VotationLocation']),
                type: isset($votationData['VoteType']) ? PolisVoteType::createFromArray($votationData['VoteType']) : null,
                mainResult: isset($votationData['VotationMainResult']) ? PolisResult::createFromArray($votationData['VotationMainResult']) : null,
                cantonalResult: isset($votationData['cantonalMajority']) ? PolisCantonalResult::createFromArray($votationData['cantonalMajority']) : null,
                cantonalResults: isset($votationData['VotationCantonalResults']) ? array_map(fn ($cantonalResult) => PolisResult::createFromArray($cantonalResult), $votationData['VotationCantonalResults']) : null,
                results: isset($votationData['allFinalResults']) ? array_map(fn ($cantonalResult) => PolisResult::createFromArray($cantonalResult), $votationData['allFinalResults']) : null,
            ), $caseData['Votations']['Votation'] ?? [])
        );
    }

    public static function parsePolisDateTime(string $dateString): ?\DateTimeImmutable
    {
        preg_match('/\/Date\((\d+)([+-]\d{4})?\)\//', $dateString, $matches);

        if ($matches) {
            $timestampMs = (int) $matches[1];
            $timestampSec = intval(round($timestampMs / 1000));

            return (new \DateTimeImmutable())->setTimestamp($timestampSec);
        }

        return null;
    }
}
