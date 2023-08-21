<?php

namespace App\Enum;

enum ECompanyType: int
{
    case VENDOR = 1;
    case DISTRIBUTOR = 2;
    case RESELLER = 3;
    case END_CUSTOMER = 4;
    case VIRTUAL_RESELLER = 5;
    case SELF = 99;
}
