<?php

namespace SRF\PolisBundle\Model\Polis;

readonly class PolisVoteType
{
    public function __construct(
        public string $id,
        public string $name,
        public bool $scoreMore,
    ) {
    }
}
