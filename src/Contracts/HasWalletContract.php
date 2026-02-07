<?php

declare(strict_types=1);

namespace Eidolex\EWallet\Contracts;

use Eidolex\EWallet\Data\TopUpData;
use Eidolex\EWallet\Data\TransferData;
use Eidolex\EWallet\Data\WithdrawData;
use Eidolex\EWallet\Models\Transaction;
use Eidolex\EWallet\Models\Transfer;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @template TName of \UnitEnum
 */
interface HasWalletContract
{
    /**
     * @return MorphOne<\Eidolex\EWallet\Models\Wallet,$this>
     */
    public function wallet(): MorphOne;

    /**
     * @return HasManyThrough<\Eidolex\EWallet\Models\Transaction,\Eidolex\EWallet\Models\Wallet,$this>
     */
    public function transactions(): HasManyThrough;

    /**
     * @param TopUpData<TName> $data
     */
    public function topUp(TopUpData $data): Transaction;

    /**
     * @param WithdrawData<TName> $data
     */
    public function withdraw(WithdrawData $data): Transaction;

    /**
     * @param TransferData<TName> $data
     */
    public function transfer(TransferData $data): Transfer;
}
