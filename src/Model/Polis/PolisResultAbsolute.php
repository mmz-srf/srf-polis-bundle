<?php

namespace SRF\PolisBundle\Model\Polis;

readonly class PolisResultAbsolute
{
    public function __construct(
        public string $yes,
        public string $no,
    ) {
    }
}
