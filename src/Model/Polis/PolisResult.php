<?php

namespace SRF\PolisBundle\Model\Polis;

use SRF\PolisBundle\Service\PolisClient;

readonly class PolisResult
{
    public function __construct(
        public PolisVoteLocation $location,
        public PolisResultAbsolute $absolute,
        public PolisResultRelative $relative,
        public PolisDataCondition $dataCondition,
        public PolisResultCondition $resultCondition,
        public ?\DateTimeImmutable $updatedAt,
    ) {
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            location: PolisVoteLocation::createFromArray($data['Location']),
            absolute: new PolisResultAbsolute(
                yes: $data['Absolute']['Yes'] ?? 0,
                no: $data['Absolute']['No'] ?? 0,
            ),
            relative: new PolisResultRelative(
                yes: $data['Relative']['Yes'] ?? 0,
                no: $data['Relative']['No'] ?? 0,
                participation: $data['Relative']['Participation'] ?? null,
            ),
            dataCondition: PolisDataCondition::from($data['DataCondition']['id']),
            resultCondition: PolisResultCondition::from($data['ResultCondition']['id']),
            updatedAt: isset($data['LastUpdate']) ? PolisClient::parsePolisDateTime($data['LastUpdate']) : null,
        );
    }
}
