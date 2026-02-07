<?php

declare(strict_types=1);

namespace Eidolex\EWallet\Enums;

enum TransactionStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
