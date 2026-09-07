<?php

namespace App\Enums;

enum ProgressiveOverflowBehavior: string
{
    case REPEAT_LAST = 'repeat_last';
    case ZERO = 'zero';
}
