<?php

namespace SRF\PolisBundle\Model\Polis;

readonly class PolisResultCantons
{
    public function __construct(
        public string $yes,
        public string $no,
    ) {
    }
}
