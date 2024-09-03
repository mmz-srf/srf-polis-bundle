<?php

namespace SRF\PolisBundle\Model\Polis;

readonly class PolisVotation
{
    public function __construct(
        public string $id,
        public string $title,
        public string $individualTitle,
        public PolisVoteLocation $location,
        public ?PolisVoteType $type,
        public ?PolisResult $mainResult,
        public ?PolisResultAbsolute $cantonalResult,
        /** @var array<PolisResult> */
        public ?array $cantonalResults,
        /** @var array<PolisResult> */
        public ?array $results,
    ) {
    }
}
