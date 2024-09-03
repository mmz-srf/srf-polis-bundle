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
}
