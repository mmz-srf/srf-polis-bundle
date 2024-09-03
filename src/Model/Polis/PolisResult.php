<?php

namespace SRF\PolisBundle\Model\Polis;

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
                yes: $data['Absolute']['Yes'],
                no: $data['Absolute']['No'],
            ),
            relative: new PolisResultRelative(
                yes: $data['Relative']['Yes'],
                no: $data['Relative']['No'],
                participation: $data['Relative']['Participation'] ?? null,
            ),
            dataCondition: PolisDataCondition::from($data['DataCondition']['id']),
            resultCondition: PolisResultCondition::from($data['ResultCondition']['id']),
            updatedAt: isset($data['LastUpdate']) ? self::parsePolisDateTime($data['LastUpdate']) : null,
        );
    }

    private static function parsePolisDateTime(string $dateString): ?\DateTimeImmutable
    {
        preg_match('/\/Date\((\d+)([+-]\d{4})?\)\//', $dateString, $matches);

        if ($matches) {
            $timestampMs = (int) $matches[1];
            $timestampSec = $timestampMs / 1000;

            return (new \DateTimeImmutable())->setTimestamp($timestampSec);
        }

        return null;
    }
}
