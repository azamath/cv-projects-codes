<?php

namespace App\Enum;

enum EStateUpdateMethod: string
{
    case MANUAL = 'manual';
    case API = 'api';
}
