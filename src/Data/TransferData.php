<?php

declare(strict_types=1);

namespace Eidolex\EWallet\Data;

use Eidolex\EWallet\Contracts\HasWalletContract;
use Eidolex\EWallet\Enums\TransactionStatus;
use Eidolex\EWallet\Models\Wallet;
use Spatie\LaravelData\Data;
use UnitEnum;

/**
 * @template-covariant TName of \UnitEnum
 * @template WalletModel of \Eidolex\EWallet\Models\Wallet = \Eidolex\EWallet\Models\Wallet
 */
class TransferData extends Data
{
    /**
     * @param TName $name
     */
    public function __construct(
        public readonly HasWalletContract $to,
        public readonly UnitEnum $name,
        public readonly int $amount,
        public readonly ?array $fromMetadata = null,
        public readonly ?array $toMetadata = null,
    ) {}

    /**
     * @param WalletModel $wallet
     */
    public function fromFields(Wallet $wallet): array
    {
        return [
            'name' => $this->name,
            'status' => TransactionStatus::Completed,
            'amount' => $this->amount,
            'metadata' => $this->fromMetadata,
        ];
    }

    /**
     * @param WalletModel $wallet
     */
    public function toFields(Wallet $wallet): array
    {
        return [
            'name' => $this->name,
            'status' => TransactionStatus::Completed,
            'amount' => $this->amount,
            'metadata' => $this->toMetadata,
        ];
    }
}
