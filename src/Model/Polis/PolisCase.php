<?php

namespace SRF\PolisBundle\Model\Polis;

readonly class PolisCase
{
    public function __construct(
        public string $id,
        public string $title,
        public bool $active,
        /** @var array<PolisVotation> */
        public array $votations,
    ) {
    }
}
