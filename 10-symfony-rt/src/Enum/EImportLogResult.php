<?php

namespace App\Enum;

enum EImportLogResult: int
{
    case PENDING = -1;
    case SUCCESS = 0;
    case FAIL = 1;
}
