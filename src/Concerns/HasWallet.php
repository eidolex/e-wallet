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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * @template TName of \UnitEnum
 * @template WalletModel of \Eidolex\EWallet\Models\Wallet = \Eidolex\EWallet\Models\Wallet
 * @template TransactionModel of \Eidolex\EWallet\Models\Transaction = \Eidolex\EWallet\Models\Transaction
 * @template TransferModel of \Eidolex\EWallet\Models\Transfer = \Eidolex\EWallet\Models\Transfer
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasWallet
{
    /**
     * @return MorphOne<WalletModel,$this>
     */
    public function wallet(): MorphOne
    {
        return $this->morphOne(
            config('e-wallet.models.wallet'),
            'owner'
        );
    }

    /**
     * @return HasManyThrough<TransactionModel,WalletModel,$this>
     */
    public function transactions(): HasManyThrough
    {
        return $this->hasManyThrough(
            config('e-wallet.models.transaction'),
            config('e-wallet.models.wallet'),
            'owner_id',
            'wallet_id',
            'owner_id',
        );
    }

    /**
     * @param TopUpData<TName> $data
     * @return TransactionModel
     */
    public function topUp(TopUpData $data): Transaction
    {
        if ($data->amount < 1) {
            throw new InvalidArgumentException('Amount must be greater than 0');
        }

        return DB::transaction(function () use ($data): Transaction {
            $wallet = $this->wallet ?: $this->wallet()->create();

            $this->setRelation('wallet', $wallet);

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
     * @return TransactionModel
     */
    public function withdraw(WithdrawData $data): Transaction
    {
        if ($data->amount < 1) {
            throw new InvalidArgumentException('Amount must be greater than 0');
        }

        return DB::transaction(function () use ($data): Transaction {
            $wallet = $this->wallet ?: $this->wallet()->create();

            if ($wallet->balance < $data->amount) {
                throw new InvalidArgumentException('Insufficient balance');
            }

            $this->setRelation('wallet', $wallet);

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
     * @return TransferModel
     */
    public function transfer(TransferData $data): Transfer
    {
        if ($data->amount < 1) {
            throw new InvalidArgumentException('Amount must be greater than 0');
        }

        return DB::transaction(function () use ($data): Transfer {
            $fromWallet = $this->wallet ?: $this->wallet()->create();

            if ($fromWallet->balance < $data->amount) {
                throw new InvalidArgumentException('Insufficient balance');
            }

            $toWallet = $data->to->wallet ?: $data->to->wallet()->create();

            $this->setRelation('wallet', $fromWallet);

            if ($data->to instanceof Model) {
                $data->to->setRelation('wallet', $toWallet);
            }

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
