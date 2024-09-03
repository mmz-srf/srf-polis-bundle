<?php

namespace SRF\PolisBundle\Model\Polis;

readonly class PolisCase
{
    public function __construct(
        public int $id,
        public string $title,
        public ?\DateTimeImmutable $date,
        public bool $active,
        /** @var array<PolisVotation> */
        public array $votations,
    ) {
    }
}
