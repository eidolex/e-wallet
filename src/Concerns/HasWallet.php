<?php

declare(strict_types=1);

namespace Eidolex\EWallet\Concerns;

use Eidolex\EWallet\Contracts\TopUpDataTransformerContract;
use Eidolex\EWallet\Contracts\TransferDataTransformerContract;
use Eidolex\EWallet\Contracts\WithdrawDataTransformerContract;
use Eidolex\EWallet\Data\TopUpData;
use Eidolex\EWallet\Data\TransferData;
use Eidolex\EWallet\Data\WithdrawData;
use Eidolex\EWallet\Enums\TransactionStatus;
use Eidolex\EWallet\Enums\TransactionType;
use Eidolex\EWallet\Models\Transaction;
use Eidolex\EWallet\Models\Transfer;
use Eidolex\EWallet\Models\Wallet;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * @template TName of \UnitEnum
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasWallet
{
    /**
     * @return MorphOne<Wallet,$this>
     */
    public function wallet(): MorphOne
    {
        return $this->morphOne(Wallet::class, 'owner');
    }

    /**
     * @return HasManyThrough<Transaction,Wallet,$this>
     */
    public function transactions(): HasManyThrough
    {
        return $this->hasManyThrough(Transaction::class, Wallet::class);
    }

    /**
     * @param TopUpData<TName> $data
     */
    public function topUp(TopUpData $data): Transaction
    {
        return DB::transaction(function () use ($data): Transaction {
            $wallet = $this->wallet()->firstOrCreate();

            /**
             * @var class-string<TopUpDataTransformerContract> $transformerClass
             */
            $transformerClass = config('e-wallet.transformers.top_up_data');

            $transformer = app($transformerClass);

            if (! $transformer instanceof TopUpDataTransformerContract) {
                throw new InvalidArgumentException('Transformer must implement TopUpDataTransformerContract');
            }

            $transaction = new Transaction($transformer->transform($data));
            $transaction->type = TransactionType::Deposit;
            $transaction->wallet()->associate($wallet);
            $transaction->save();

            if ($transaction->status === TransactionStatus::Completed) {
                $wallet->increment('balance', $data->amount);
            }

            return $transaction;
        }, 3);
    }

    /**
     * @param WithdrawData<TName> $data
     */
    public function withdraw(WithdrawData $data): Transaction
    {
        return DB::transaction(function () use ($data): Transaction {
            $wallet = $this->wallet()->firstOrCreate();

            /**
             * @var class-string<WithdrawDataTransformerContract> $transformerClass
             */
            $transformerClass = config('e-wallet.transformers.withdraw_data');

            $transformer = app($transformerClass);

            if (! $transformer instanceof WithdrawDataTransformerContract) {
                throw new InvalidArgumentException('Transformer must implement WithdrawDataTransformerContract');
            }

            $transaction = new Transaction($transformer->transform($data));
            $transaction->type = TransactionType::Withdraw;
            $transaction->wallet()->associate($wallet);
            $transaction->save();

            if ($transaction->status === TransactionStatus::Completed) {
                $wallet->decrement('balance', $data->amount);
            }

            return $transaction;
        }, 3);
    }

    /**
     * @param TransferData<TName> $data
     */
    public function transfer(TransferData $data): Transfer
    {
        return DB::transaction(function () use ($data): Transfer {
            $fromWallet = $this->wallet()->firstOrCreate();
            $toWallet = $data->to->wallet()->firstOrCreate();

            /**
             * @var class-string<TransferDataTransformerContract> $fromTransformerClass
             * @var class-string<TransferDataTransformerContract> $toTransformerClass
             */
            $fromTransformerClass = config('e-wallet.transformers.transfer_from_data');
            $toTransformerClass = config('e-wallet.transformers.transfer_to_data');

            $fromTransformer = app($fromTransformerClass);
            $toTransformer = app($toTransformerClass);

            if (! $fromTransformer instanceof TransferDataTransformerContract) {
                throw new InvalidArgumentException('Transformer must implement TransferDataTransformerContract');
            }

            if (! $toTransformer instanceof TransferDataTransformerContract) {
                throw new InvalidArgumentException('Transformer must implement TransferDataTransformerContract');
            }

            $fromTransaction = new Transaction($fromTransformer->transform($data));
            $fromTransaction->type = TransactionType::Withdraw;
            $fromTransaction->wallet()->associate($fromWallet);
            $fromTransaction->save();

            $toTransaction = new Transaction($toTransformer->transform($data));
            $toTransaction->type = TransactionType::Deposit;
            $toTransaction->wallet()->associate($toWallet);
            $toTransaction->save();

            $transfer = Transfer::query()->create([
                'from_transaction_id' => $fromTransaction->id,
                'to_transaction_id' => $toTransaction->id,
                'amount' => $data->amount,
            ]);

            if ($fromTransaction->status === TransactionStatus::Completed) {
                $fromWallet->decrement('balance', $data->amount);
            }

            if ($toTransaction->status === TransactionStatus::Completed) {
                $toWallet->increment('balance', $data->amount);
            }

            $transfer->setRelations([
                'from' => $fromTransaction,
                'to' => $toTransaction,
            ]);

            return $transfer;
        }, 3);
    }
}
