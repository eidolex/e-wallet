<?php

declare(strict_types=1);

namespace Eidolex\EWallet\Enums;

enum TransactionStatus: int
{
    case Pending = 0;
    case Completed = 1;
    case Cancelled = 2;
    case Failed = 3;
    case Refunded = 4;
}
