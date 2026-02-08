<?php

declare(strict_types=1);

namespace Eidolex\EWallet\Contracts;

use Eidolex\EWallet\Data\TransferData;
use Eidolex\EWallet\Models\Wallet;

/**
 * @template WalletModel of \Eidolex\EWallet\Models\Wallet = \Eidolex\EWallet\Models\Wallet
 */
interface TransferDataTransformerContract
{
    /**
     * @param WalletModel $wallet
     */
    public function transform(Wallet $wallet, TransferData $data): array;
}
