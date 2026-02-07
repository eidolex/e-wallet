<?php

declare(strict_types=1);

namespace Eidolex\EWallet\Data;

use Eidolex\EWallet\Enums\TransactionStatus;
use Spatie\LaravelData\Data;
use UnitEnum;

/**
 * @template-covariant TName of \UnitEnum
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
}
