<?php

namespace SRF\PolisBundle\Model\Polis;

readonly class PolisResult
{
    public function __construct(
        public PolisVoteLocation $location,
        public PolisResultAbsolute $absolute,
        public PolisResultRelative $relative,
        public PolisDataCondition $dataCondition,
        public PolisResultCondition $resultCondition,
    ) {
    }
}
