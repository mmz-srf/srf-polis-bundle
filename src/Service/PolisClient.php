<?php

namespace SRF\PolisBundle\Service;

use SRF\PolisBundle\Model\Polis\PolisCase;
use SRF\PolisBundle\Model\Polis\PolisDataCondition;
use SRF\PolisBundle\Model\Polis\PolisResult;
use SRF\PolisBundle\Model\Polis\PolisResultAbsolute;
use SRF\PolisBundle\Model\Polis\PolisResultCondition;
use SRF\PolisBundle\Model\Polis\PolisResultRelative;
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

    public function fetchPolisVotationById(string $id, string $language): PolisVotation
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

                foreach ($item['Results'] as $result) {
                    if ('Item3' === $result['dataConditionID']) {
                        foreach ($result['Result'] as $resultItem) {
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
                $item['cantonalMajority'] = $cantonalMajority;

                $case['Votations'] = [
                    'Votation' => [$item],
                ];
            }
        }

        return $this->denormalizePolisCase($case)->votations[0];
    }

    public function fetchPolisCaseById(string $id, string $language = 'de'): PolisCase
    {
        $caseData = $this->client->request('GET', sprintf('/polis-api/v2/cases/%s?lang=%s', $id, $language));
        $data = $caseData->toArray();

        return $this->denormalizePolisCase($data['Case'][0]);
    }

    public function fetchPolisCases(string $language = 'de', bool $onlyActive = true): array
    {
        $caseData = $this->client->request('GET', sprintf('/polis-api/v2/cases?lang=%s&listAllCases=%s', $language, $onlyActive ? 'false' : 'true'));
        $data = $caseData->toArray();

        return array_map(fn ($caseData) => $this->denormalizePolisCase($caseData), $data['Case'] ?? []);
    }

    private function denormalizePolisCase(array $caseData): PolisCase
    {
        $case = new PolisCase(
            id: $caseData['id'],
            title: $caseData['Title'],
            active: $caseData['active'],
            votations: array_map(fn ($votationData) => new PolisVotation(
                id: $votationData['id'],
                title: $votationData['Title'],
                individualTitle: $votationData['IndividualTitle'],
                location: new PolisVoteLocation(
                    id: $votationData['VotationLocation']['id'],
                    locationName: $votationData['VotationLocation']['LocationName'],
                    shortName: $votationData['VotationLocation']['ShortName'],
                    type: $votationData['VotationLocation']['LocationType']['Value'],
                ),
                type: isset($votationData['VoteType']) ? new PolisVoteType(
                    id: $votationData['VoteType']['id'],
                    name: $votationData['VoteType']['Name'],
                    scoreMore: $votationData['VoteType']['ScoreMore']
                ) : null,
                mainResult: isset($votationData['VotationMainResult']) ? new PolisResult(
                    location: new PolisVoteLocation(
                        id: $votationData['VotationLocation']['id'],
                        locationName: $votationData['VotationLocation']['LocationName'],
                        shortName: $votationData['VotationLocation']['ShortName'],
                        type: $votationData['VotationLocation']['LocationType']['Value'],
                        electionPower: $votationData['VotationLocation']['ElectionPower'] ?? null,
                    ),
                    absolute: new PolisResultAbsolute(
                        yes: $votationData['VotationMainResult']['Absolute']['Yes'],
                        no: $votationData['VotationMainResult']['Absolute']['No'],
                    ),
                    relative: new PolisResultRelative(
                        yes: $votationData['VotationMainResult']['Relative']['Yes'],
                        no: $votationData['VotationMainResult']['Relative']['No'],
                        participation: $votationData['VotationMainResult']['Relative']['Participation'] ?? null,
                    ),
                    dataCondition: PolisDataCondition::from($votationData['VotationMainResult']['DataCondition']['id']),
                    resultCondition: PolisResultCondition::from($votationData['VotationMainResult']['ResultCondition']['id']),
                ) : null,
                cantonalResult: isset($votationData['cantonalMajority']) ? new PolisResultAbsolute(yes: $votationData['cantonalMajority']['yes'], no: $votationData['cantonalMajority']['no']) : null,
                cantonalResults: isset($votationData['VotationCantonalResults']) ? array_map(fn ($cantonalResult) => new PolisResult(
                    location: new PolisVoteLocation(
                        id: $cantonalResult['Location']['id'],
                        locationName: $cantonalResult['Location']['LocationName'],
                        shortName: $cantonalResult['Location']['ShortName'],
                        type: $cantonalResult['Location']['LocationType']['Value'],
                        electionPower: $cantonalResult['Location']['ElectionPower'] ?? null,
                    ),
                    absolute: new PolisResultAbsolute(
                        yes: $cantonalResult['Absolute']['Yes'],
                        no: $cantonalResult['Absolute']['No'],
                    ),
                    relative: new PolisResultRelative(
                        yes: $cantonalResult['Relative']['Yes'],
                        no: $cantonalResult['Relative']['No'],
                        participation: $cantonalResult['Relative']['Participation'] ?? null,
                    ),
                    dataCondition: PolisDataCondition::from($cantonalResult['DataCondition']['id']),
                    resultCondition: PolisResultCondition::from($cantonalResult['ResultCondition']['id']),
                ), $votationData['VotationCantonalResults']) : null,
            ), $caseData['Votations']['Votation'] ?? [])
        );

        return $case;
    }
}
