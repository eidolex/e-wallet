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
 * @template WalletModel of \Eidolex\EWallet\Models\Wallet
 * @template TransactionModel of \Eidolex\EWallet\Models\Transaction
 * @template TransferModel of \Eidolex\EWallet\Models\Transfer
 */
interface HasWalletContract
{
    /**
     * @return MorphOne<WalletModel,$this>
     */
    public function wallet(): MorphOne;

    /**
     * @return HasManyThrough<TransactionModel,WalletModel,$this>
     */
    public function transactions(): HasManyThrough;

    /**
     * @param TopUpData<TName> $data
     * @return TransactionModel
     */
    public function topUp(TopUpData $data): Transaction;

    /**
     * @param WithdrawData<TName> $data
     * @return TransactionModel
     */
    public function withdraw(WithdrawData $data): Transaction;

    /**
     * @param TransferData<TName> $data
     * @return TransferModel
     */
    public function transfer(TransferData $data): Transfer;
}
