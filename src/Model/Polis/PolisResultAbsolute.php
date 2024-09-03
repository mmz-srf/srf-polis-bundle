<?php

namespace SRF\PolisBundle\Model\Polis;

readonly class PolisResultAbsolute
{
    public function __construct(
        public int $yes,
        public int $no,
    ) {
    }
}
