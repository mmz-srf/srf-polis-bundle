<?php

namespace SRF\PolisBundle\Model\Polis;

readonly class PolisVoteLocation
{
    public function __construct(
        public string $id,
        public string $locationName,
        public string $shortName,
        public string $type,
        public ?float $electionPower = null,
    ) {
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            locationName: $data['LocationName'],
            shortName: $data['ShortName'],
            type: $data['LocationType']['Value'],
            electionPower: $data['ElectionPower'] ?? null,
        );
    }
}
