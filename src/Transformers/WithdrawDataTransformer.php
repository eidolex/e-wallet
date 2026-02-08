<?php

declare(strict_types=1);

namespace Eidolex\EWallet\Transformers;

use Eidolex\EWallet\Contracts\WithdrawDataTransformerContract;
use Eidolex\EWallet\Data\WithdrawData;
use Eidolex\EWallet\Models\Wallet;

class WithdrawDataTransformer implements WithdrawDataTransformerContract
{
    public function transform(Wallet $wallet, WithdrawData $data): array
    {
        return [
            'name' => $data->name,
            'amount' => $data->amount,
            'status' => $data->status,
            'metadata' => $data->metadata,
        ];
    }
}
