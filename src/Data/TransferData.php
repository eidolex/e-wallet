<?php

declare(strict_types=1);

namespace Eidolex\EWallet\Data;

use Eidolex\EWallet\Contracts\HasWalletContract;
use Spatie\LaravelData\Data;
use UnitEnum;

/**
 * @template-covariant TName of \UnitEnum
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
}
