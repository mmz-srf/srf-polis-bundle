<?php

namespace SRF\PolisBundle\Model\Polis;

enum PolisResultCondition: string
{
    case CONDITION_UNKNOWN = 'Item1';
    case VOTATION_ACCEPTED = 'Item2';
    case VOTATION_DECLINED = 'Item3';
}
