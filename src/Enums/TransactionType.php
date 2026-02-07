<?php

declare(strict_types=1);

namespace Eidolex\EWallet\Enums;

enum TransactionType: string
{
    case Deposit = 'deposit';
    case Withdraw = 'withdraw';
}
