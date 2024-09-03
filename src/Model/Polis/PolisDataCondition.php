<?php

namespace SRF\PolisBundle\Model\Polis;

enum PolisDataCondition: string
{
    case CONDITION_RESULT = 'Item3';
    case CONDITION_EXTRAPOL1 = 'Item4';
    case CONDITION_EXTRAPOL2 = 'Item8';
    case CONDITION_EXTRAPOL3 = 'Item9';
    case CONDITION_WEB = 'Item5';
    case CONDITION_TREND = 'Item6';
    case CONDITION_UNDEF = 'Item7';

    public function getId(): int
    {
        return intval(str_replace('Item', '', $this->value));
    }

    public function getLabel(): string
    {
        return $this->name;
    }
}
