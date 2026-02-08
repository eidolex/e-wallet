<?php

declare(strict_types=1);

namespace Eidolex\EWallet\Enums;

enum TransactionType: int
{
    case Withdraw = 0;
    case Deposit = 1;
}
