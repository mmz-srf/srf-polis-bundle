<?php

namespace SRF\PolisBundle\Model\Polis;

readonly class PolisVoteLocationType
{
    public function __construct(
        public int $id,
        public string $value,
    ) {
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            value: $data['Value'] ?? 'Unbekannt',
        );
    }
}
