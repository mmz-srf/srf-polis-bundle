<?php

namespace SRF\PolisBundle\Model\Polis;

readonly class PolisVotation
{
    public function __construct(
        public int $id,
        public string $title,
        public string $individualTitle,
        public PolisVoteLocation $location,
        public ?PolisVoteType $type,
        public ?PolisResult $mainResult,
        public ?PolisCantonalResult $cantonalResult,
        /** @var array<PolisResult> */
        public ?array $cantonalResults,
        /** @var array<PolisResult> */
        public ?array $results,
    ) {
    }
}
