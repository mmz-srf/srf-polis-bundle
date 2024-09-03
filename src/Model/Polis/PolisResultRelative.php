<?php

namespace SRF\PolisBundle\Model\Polis;

readonly class PolisResultRelative
{
    public function __construct(
        public float $yes,
        public float $no,
        public ?float $participation,
    ) {
    }
}
