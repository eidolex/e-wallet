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
 * @template TOwner of \Illuminate\Database\Eloquent\Model
 * @template TName of \UnitEnum
 * @template WalletModel of \Eidolex\EWallet\Models\Wallet = \Eidolex\EWallet\Models\Wallet
 * @template TransactionModel of \Eidolex\EWallet\Models\Transaction = \Eidolex\EWallet\Models\Transaction
 * @template TransferModel of \Eidolex\EWallet\Models\Transfer = \Eidolex\EWallet\Models\Transfer
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 * @mixin HasWalletContract<TOwner,TName,WalletModel,TransactionModel,TransferModel>
 */
trait HasWallet
{
    public function wallet(): MorphOne
    {
        /**
         * @var class-string<WalletModel> $class
         */
        $class = config('e-wallet.models.wallet');

        return $this->morphOne( // @phpstan-ignore return.type (TDeclaringModel on MorphOne is invariant)
            $class,
            'owner'
        );
    }

    public function transactions(): HasManyThrough
    {
        /**
         * @var class-string<TransactionModel> $transactionClass
         */
        $transactionClass = config('e-wallet.models.transaction');

        /**
         * @var class-string<WalletModel> $walletClass
         */
        $walletClass = config('e-wallet.models.wallet');

        return $this->hasManyThrough( // @phpstan-ignore return.type (TDeclaringModel on HasManyThrough is invariant)
            $transactionClass,
            $walletClass,
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
            $wallet = $this->getWallet();

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
            $wallet = $this->getWallet();

            if ($wallet->balance < $data->amount) {
                throw new InvalidArgumentException('Insufficient balance');
            }

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
            $fromWallet = $this->getWallet();

            if ($fromWallet->balance < $data->amount) {
                throw new InvalidArgumentException('Insufficient balance');
            }

            $toWallet = $data->to->getWallet();

            /**
             * @var class-string<TransferDataTransformerContract> $fromTransformerClass
             */
            $fromTransformerClass = config('e-wallet.transformers.transfer_from_data');
            /**
             * @var class-string<TransferDataTransformerContract> $toTransformerClass
             */
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

    /**
     * 
     * @return WalletModel
     */
    public function getWallet(): Wallet
    {
        $this->loadMissing('wallet');

        /**
         * @var WalletModel|null $wallet
         */
        $wallet = $this->getRelation('wallet');

        if (! $wallet) {
            $wallet = $this->wallet()->create();
            $this->setRelation('wallet', $wallet);
        }

        return $wallet;
    }
}
