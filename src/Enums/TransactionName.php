<?php

declare(strict_types=1);

namespace Eidolex\EWallet\Enums;

enum TransactionName: string
{
    case TopUp = 'top_up';
    case Withdraw = 'withdraw';
    case Gift = 'gift';
    case Purchase = 'purchase';
}
