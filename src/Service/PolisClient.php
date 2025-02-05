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

    public function fetchPolisCaseByVotationId(int $id, string $language, bool $fetchCommunalResultData = false): PolisCase
    {
        // by default, Polis only delivers results for locations types 1 - 3 (national, cantonal and district)
        $locationTypeIds = '1,2,3';
        // if we need communal data (which we only do for communal votations), we need to explicitly request them
        if ($fetchCommunalResultData) {
            $locationTypeIds = '4';
        }

        $votationResponse = $this->client->request('GET', sprintf('/polis-api/v2/votations/%s?lang=%s&locationtypeid=%s', $id, $language, $locationTypeIds));
        $data = $votationResponse->toArray();

        $case = [];
        foreach ($data['Items'] as &$item) {
            if ('tpc.eMedia.PolisV2.Business.API.v1.Models.Export.Case, tpc.eMedia.PolisV2.Business' === $item['__type']) {
                $case = $item;
            } elseif ('tpc.eMedia.PolisV2.Business.API.v1.Models.Export.Votation, tpc.eMedia.PolisV2.Business' === $item['__type']) {
                $item['VotationCantonalResults'] = [];
                $location = PolisVoteLocation::createFromArray($item['VotationLocation']);
                // if this is a communal votation, we need to explicitly fetch communal result data (that gets ignored by default)
                if (4 === $location->type->id && false === $fetchCommunalResultData) {
                    return $this->fetchPolisCaseByVotationId($id, $language, true);
                }

                $cantonalMajority = [
                    'yes' => 0,
                    'no' => 0,
                ];
                $allResults = [];
                foreach ($item['Results'] as $result) {
                    // data condition = state of the result, e.g. prognosis, final result, ...
                    if ('Item3' === $result['dataConditionID']) {
                        foreach ($result['Result'] as $resultItem) {
                            $allResults[] = $resultItem;

                            // hey look, the main result was provided:
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

                    // if main result was NOT provided AND it's a national votation, we have to manually sum up the cantonal results
                    if (!isset($item['VotationMainResult']) && '1' === $item['VotationLocation']['id']) {

                        $absoluteYes = 0;
                        $absoluteNo = 0;

                        foreach ($item['VotationCantonalResults'] as $cantonalResult) {
                            $absoluteYes += $cantonalResult['Absolute']['Yes'];
                            $absoluteNo += $cantonalResult['Absolute']['No'];
                        }

                        $relativeYes = 0 === $absoluteYes
                            ? 0
                            : 100 / ($absoluteYes + $absoluteNo) * $absoluteYes;
                        $relativeNo = 0 === $absoluteNo
                            ? 0
                            : 100 / ($absoluteYes + $absoluteNo) * $absoluteNo;

                        $item['VotationMainResult'] = [
                            'Location' => $item['VotationLocation'],
                            'Absolute' => [
                                'Yes' => $absoluteYes,
                                'No' => $absoluteNo,
                            ],
                            'Relative' => [
                                'Yes' => round($relativeYes, 1),
                                'No' => round($relativeNo, 1),
                            ],
                            'DataCondition' => [
                                'id' => 'Item3',
                            ],
                            'ResultCondition' => [
                                'id' => 'Item1',
                            ],
                        ];
                    }
                }

                // calc relatives (in relation to 23 cantons/half cantons)
                $relatives = [
                    'yes' => 0 === $cantonalMajority['yes'] ? 0 : 100 / 23 * $cantonalMajority['yes'],
                    'no' => 0 === $cantonalMajority['no'] ? 0 : 100 / 23 * $cantonalMajority['no'],
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
        return $this->fetchPolisCaseByVotationId($id, $language)->votations[0];
    }

    public function fetchPolisCaseById(int $id, string $language = 'de'): PolisCase
    {
        $caseData = $this->client->request('GET', sprintf('/polis-api/v2/cases/%s?lang=%s', $id, $language));
        $data = $caseData->toArray();

        return $this->denormalizePolisCase($data['Case'][0]);
    }

    public function fetchPolisCases(string $language = 'de', bool $onlyActive = true): array
    {
        $caseData = $this->client->request('GET', sprintf(
            '/polis-api/v2/cases?lang=%s&listAllCases=%s',
            $language,
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
