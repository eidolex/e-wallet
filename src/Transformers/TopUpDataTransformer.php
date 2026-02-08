<?php

declare(strict_types=1);

namespace Eidolex\EWallet\Transformers;

use Eidolex\EWallet\Contracts\TopUpDataTransformerContract;
use Eidolex\EWallet\Data\TopUpData;
use Eidolex\EWallet\Models\Wallet;

class TopUpDataTransformer implements TopUpDataTransformerContract
{
    public function transform(Wallet $wallet, TopUpData $data): array
    {
        return [
            'name' => $data->name,
            'amount' => $data->amount,
            'status' => $data->status,
            'metadata' => $data->metadata,
        ];
    }
}
