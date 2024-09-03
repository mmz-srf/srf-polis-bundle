<?php

namespace SRF\PolisBundle\Model\Polis;

readonly class PolisVoteType
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $scoreMore,
    ) {
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['Name'],
            scoreMore: $data['ScoreMore']
        );
    }
}
