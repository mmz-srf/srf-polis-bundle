<?php

namespace SRF\PolisBundle\Model\Polis;

readonly class PolisResultRelative
{
    public function __construct(
        public string $yes,
        public string $no,
        public ?string $participation,
    ) {
    }
}
