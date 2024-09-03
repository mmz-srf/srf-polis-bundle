<?php

namespace SRF\PolisBundle\Model\Polis;

readonly class PolisCantonalResult
{
    public function __construct(
        public PolisResultAbsolute $absolute,
        public PolisResultRelative $relative,
    ) {
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            absolute: new PolisResultAbsolute(yes: $data['absolute']['yes'], no: $data['absolute']['no']),
            relative: new PolisResultRelative(yes: $data['relative']['yes'], no: $data['relative']['no'], participation: null),
        );
    }
}
