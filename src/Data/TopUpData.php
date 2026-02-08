<?php

declare(strict_types=1);

namespace Eidolex\EWallet\Data;

use Eidolex\EWallet\Enums\TransactionStatus;
use Eidolex\EWallet\Models\Wallet;
use Spatie\LaravelData\Data;
use UnitEnum;

/**
 * @template-covariant TName of \UnitEnum
 * @template WalletModel of \Eidolex\EWallet\Models\Wallet = \Eidolex\EWallet\Models\Wallet
 */
class TopUpData extends Data
{
    /**
     * @param TName $name
     */
    public function __construct(
        public readonly UnitEnum $name,
        public readonly int $amount,
        public readonly TransactionStatus $status = TransactionStatus::Completed,
        public readonly ?array $metadata = null,
    ) {}

    /**
     * @param WalletModel $wallet
     */
    public function fields(Wallet $wallet): array
    {
        return [
            'name' => $this->name,
            'amount' => $this->amount,
            'status' => $this->status,
            'metadata' => $this->metadata,
        ];
    }
}
