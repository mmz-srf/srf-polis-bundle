<?php

namespace SRF\PolisBundle\Model\Polis;

readonly class PolisVoteLocation
{
    public function __construct(
        public int $id,
        public string $locationName,
        public string $shortName,
        public PolisVoteLocationType $type,
        public ?int $parentLocationId,
        public ?float $electionPower = null,
    ) {
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            locationName: $data['LocationName'],
            shortName: $data['ShortName'],
            type: PolisVoteLocationType::createFromArray($data['LocationType']),
            parentLocationId: $data['ParentLocationID'] ?? null,
            electionPower: $data['ElectionPower'] ?? null,
        );
    }
}
